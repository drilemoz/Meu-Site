#!/usr/bin/env python3
"""Gera o fonte do Meu Site a partir de uma árvore exclusiva e auditada.

Não confia em diretórios de builds de outros projetos, como Gradle/Android.
    python3 tools/build_source_package.py --output /caminho/Meu-Site-fonte.zip
"""
from __future__ import annotations
import argparse
import re
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED

ROOT=Path(__file__).resolve().parents[1]
ZIP_TEST_FIXTURES={'tests/fixtures/backup133_duplicate_names.zip'}
ROOT_FILES={'.gitignore','AGENTS.md','README.md','CHANGELOG.md','RELEASE_NOTES.md',
            'VERSION','composer.json','composer.lock','github-manager.json','app_identity.json'}
TOOLS={'build_css.py','build_news_editor.py','build_update_package.py',
       'build_source_package.py','news_editor_view.sha256','news_explain.php',
       'sign_manifest.php','test_chunked_upload_150.py'}
DISALLOWED_SUFFIXES={'.kt','.kts','.gradle','.jar','.class','.apk','.aab',
                     '.p12','.jks','.pem','.key','.sql','.sqlite','.db','.zip','.log'}

def allowed(p:Path)->bool:
    if not p.is_file() or p.is_symlink():return False
    rel=p.relative_to(ROOT).as_posix(); parts=rel.split('/')
    # ZIP de teste com duplicatas reais, explicitamente permitido por nome.
    # Nunca incluir backups ou outros ZIPs que surgirem em tests/.
    if rel in ZIP_TEST_FIXTURES:return True
    if p.suffix.lower() in DISALLOWED_SUFFIXES:return False
    if rel in ROOT_FILES:return True
    if rel=='.github/workflows/integration-mariadb.yml':return True
    if len(parts)<2:return False
    if parts[0]=='app':
        return parts[1]!='src' and p.name not in {'config.php','.setup.lock','.env','build.gradle.kts'}
    if parts[0]=='public':
        if parts[1]=='uploads':return rel in {'public/uploads/.htaccess','public/uploads/.gitkeep'}
        return rel!='public/ads.txt'
    if parts[0]=='storage':return p.name=='.gitkeep'
    if parts[0]=='tools':return len(parts)==2 and p.name in TOOLS
    if parts[0] in {'bin','tests'}:return True
    if parts[0]=='docs':
        return not (p.name.startswith('VALIDATION') or '0.1.' in p.name or
            p.name in {'ANIMAR_IMAGENS.md','TUTORIAL_LEGENDAS_AUTOMATICAS.md','FEATURE_MATRIX.md'})
    return False

def validate(files:list[Path])->None:
    names=[p.relative_to(ROOT).as_posix() for p in files]
    assert all('src/main/java/' not in n and '/gradle/' not in n for n in names)
    assert not any(n.startswith('design/') or n.endswith('.kt') for n in names)
    assert not any(n.endswith(('gradlew','build.gradle.kts','settings.gradle.kts')) for n in names)
    if not ZIP_TEST_FIXTURES.issubset(set(names)):
        raise ValueError('Fixture obrigatório de ZIP com duplicatas não empacotado')
    if any(n.endswith('.zip') and n not in ZIP_TEST_FIXTURES for n in names):
        raise ValueError('ZIP não permitido incluído no fonte')
    version=(ROOT/'VERSION').read_text().strip()
    if not re.fullmatch(r'\d+\.\d+\.\d+',version):raise ValueError('VERSION inválida')
    for name in ('github-manager.json','app_identity.json'):
        import json
        info=json.loads((ROOT/name).read_text())
        if info['version']!=version or info['displayName']!='Meu Site' or info.get('projectType')!='web':
            raise ValueError(f'{name}: identidade/versão inconsistente')
    if not (ROOT/'app/update-origin.json').read_text().strip().endswith('"drilemoz/Meu-Site"}'):
        raise ValueError('Origem da atualização não é a do Meu Site')

def main():
    cli=argparse.ArgumentParser();cli.add_argument('--output',required=True,type=Path)
    opts=cli.parse_args();out=opts.output.resolve()
    if out.is_relative_to(ROOT.resolve()):raise ValueError('Saída deve ficar fora do projeto')
    files=sorted((p for p in ROOT.rglob('*') if allowed(p)),key=lambda p:p.relative_to(ROOT).as_posix())
    validate(files)
    out.parent.mkdir(parents=True,exist_ok=True)
    with ZipFile(out,'w',ZIP_DEFLATED,compresslevel=8) as z:
        for p in files:z.write(p,p.relative_to(ROOT).as_posix())
    with ZipFile(out) as z:
        assert z.testzip() is None
        assert len(z.namelist())==len(set(z.namelist()))
    print(f'Fonte limpo: {len(files)} arquivos, {out.stat().st_size} bytes')
if __name__=='__main__':main()
