#!/usr/bin/env python3
"""Auditoria estática, offline, do pacote Meu Site PHP (sem prometer teste de produção)."""
from __future__ import annotations
import json
import re
from pathlib import Path
from zipfile import ZipFile

ROOT=Path(__file__).resolve().parents[1]
FIXTURE='tests/fixtures/backup133_duplicate_names.zip'
BAD_SUFFIX={'.kt','.kts','.apk','.aab','.jar','.class','.jks','.p12','.pem','.key','.sqlite','.db'}
BAD_NAMES={'build.gradle','build.gradle.kts','settings.gradle.kts','gradlew','gradlew.bat','AndroidManifest.xml'}
EXPECTED_REPO='drilemoz/Meu-Site'


def check() -> None:
    version=(ROOT/'VERSION').read_text(encoding='utf-8').strip()
    if not re.fullmatch(r'\d+\.\d+\.\d+', version):raise ValueError('VERSION inválida')
    for rel in ('public/index.php','public/admin.php','app/bootstrap.php','app/backup.php',
                'app/schema_migrations.php','app/admin_views/routes/updates.php',
                'app/admin_views/routes/backups.php','tools/build_css.py',
                'tools/build_news_editor.py','tools/build_update_package.py',
                'tools/build_source_package.py',FIXTURE):
        if not (ROOT/rel).is_file():raise ValueError('Arquivo essencial ausente: '+rel)
    for rel in ('github-manager.json','app_identity.json'):
        info=json.loads((ROOT/rel).read_text(encoding='utf-8'))
        if info.get('version')!=version or info.get('projectType')!='web' or info.get('repository')!=EXPECTED_REPO:
            raise ValueError('Metadados inconsistentes: '+rel)
    origin=json.loads((ROOT/'app/update-origin.json').read_text(encoding='utf-8'))
    if origin.get('repository')!=EXPECTED_REPO:raise ValueError('Origem de atualizações inconsistente')
    schema=(ROOT/'app/schema_migrations.php').read_text(encoding='utf-8')
    if not re.search(r'const\s+IGUA_SCHEMA_REVISION\s*=\s*37\b',schema):
        raise ValueError('Schema 37 divergente: migração exige instalação assistida')
    # Segredos reais, ou arquivos de outro projeto, não pertencem ao pacote de fonte.
    checked=0
    for file in ROOT.rglob('*'):
        if not file.is_file():continue
        rel=file.relative_to(ROOT).as_posix();parts=rel.split('/')
        checked+=1
        if file.is_symlink():raise ValueError('Symlink não permitido: '+rel)
        if file.name in BAD_NAMES or file.suffix.lower() in BAD_SUFFIX or 'uaicortes' in rel.lower() or rel.startswith(('design/','app/src/')):
            raise ValueError('Arquivo de outro projeto ou binário sensível: '+rel)
        # Testes e uso real podem gerar logs/caches privados em storage/.
        # Os geradores de ZIP já os excluem; não exigir diretório runtime vazio.
        if parts[0]=='storage' and file.name!='.gitkeep':continue
        if parts[0]=='public' and len(parts)>1 and parts[1]=='uploads' and file.name not in {'.htaccess','.gitkeep'}:
            raise ValueError('Upload de usuário dentro do fonte: '+rel)
        if file.suffix.lower()=='.zip' and rel!=FIXTURE:raise ValueError('ZIP aninhado indevido: '+rel)
    with ZipFile(ROOT/FIXTURE) as z:
        duplicates=len(z.namelist())-len(set(z.namelist()))
        if duplicates<1:raise ValueError('Fixture de ZIP duplicado não tem duplicata real')
    backup=(ROOT/'app/backup.php').read_text(encoding='utf-8')
    if not all(token in backup for token in ('function make_backup(', 'function make_media_bundle(',
                'function restore_backup(', 'function restore_media_bundle(',
                "'assets/images/'", "'assets/docs/'",'MEDIA_BUNDLE_MAX_MEDIA_BYTES')):
        raise ValueError('API e limites de backup alterados; revisar contrato')
    for rel in ('README.md','AGENTS.md','CHANGELOG.md','RELEASE_NOTES.md'):
        if version not in (ROOT/rel).read_text(encoding='utf-8'):
            raise ValueError('Documentação de versão inconsistente: '+rel)
    docs=[ROOT/'README.md',ROOT/'AGENTS.md',ROOT/'RELEASE_NOTES.md']+sorted((ROOT/'docs').glob('*.md'))
    for f in docs:
        content=f.read_text(encoding='utf-8')
        for link in re.findall(r'docs/[A-Za-z0-9_.-]+\.md',content):
            if not (ROOT/link).is_file():raise ValueError(f'Referência documental inexistente: {f.name}: {link}')
        for link in re.findall(r'\]\(([^#)]+\.md)(?:#[^)]+)?\)',content):
            if link.startswith(('https://','http://')):continue
            if not (f.parent/link).is_file():raise ValueError(f'Link Markdown quebrado: {f.name}: {link}')
    for prefix in ('VALIDACAO_','RELATORIO_ARQUIVOS_LINHAS_','ATUALIZADOR_'):
        if list((ROOT/'docs').glob(prefix+'*.md')):raise ValueError('Relatórios de versão obsoletos ainda presentes: '+prefix)
    print(f'Auditoria de estrutura aprovada: {checked} arquivos, versão {version}, doc atual, fixture de duplicata real')


if __name__=='__main__':check()
