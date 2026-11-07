#!/usr/bin/env python3
"""Gera ZIP v2 com manifesto SHA-256 (assinatura opcional).

Uso sem chave:
 python3 tools/build_update_package.py --output /tmp/Atualizacao-v1.0.63.zip

Opcional: --key-file /caminho/privado.key adiciona release.sig Ed25519.
A assinatura é a única proteção criptográfica da autoria; SHA-256 sozinho
apenas verifica os arquivos contra o manifesto do próprio ZIP.
"""
from __future__ import annotations
import argparse
import base64
import os
import re
from pathlib import Path
import hashlib
import json
import subprocess
import tempfile
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED

ROOT=Path(__file__).resolve().parents[1]
MAX_FILES=800

def verified_project_repository() -> str | None:
    """Detecta o repo do checkout, nunca de metadados de OUTRO projeto."""
    repo = ''
    if os.getenv('GITHUB_ACTIONS') == 'true':
        repo = os.getenv('GITHUB_REPOSITORY', '')
    else:
        try:
            top = subprocess.run(['git','-C',str(ROOT),'rev-parse','--show-toplevel'],capture_output=True,text=True,timeout=3)
            if top.returncode == 0 and Path(top.stdout.strip()).resolve() == ROOT.resolve():
                remote = subprocess.run(['git','-C',str(ROOT),'remote','get-url','origin'],capture_output=True,text=True,timeout=3)
                if remote.returncode == 0:
                    repo = remote.stdout.strip()
        except (OSError, subprocess.TimeoutExpired):
            pass
    m = re.fullmatch(r'(?:https://github\.com/|git@github\.com:)?([A-Za-z0-9-]{1,39})/([A-Za-z0-9._-]{1,100})(?:\.git)?/?',repo)
    if not m: return None
    name = re.sub(r'\.git$', '', m.group(2), flags=re.IGNORECASE)
    if not name or '..' in name: return None
    return m.group(1) + '/' + name

def allowed(p:Path)->bool:
    rel=p.relative_to(ROOT).as_posix()
    parts=rel.split('/')
    if p.is_symlink() or not p.is_file() or any(s in {'.','..',''} for s in parts):return False
    if rel=='VERSION':return True
    if rel=='public/ads.txt':return False # configuração gerada na hospedagem; preservar
    if parts[0] not in ('app','public') or len(parts)<2:return False
    if parts[:2]==['app','src'] or parts[:2]==['public','uploads']:return False
    if p.suffix.lower() not in {'.php','.json','.css','.js','.html','.htm','.txt','.md','.svg','.png','.jpg','.jpeg','.gif','.webp','.ico','.woff','.woff2','.ttf','.otf','.map','.xml','.htaccess'} and p.name not in {'.htaccess','.gitkeep'}:return False
    if p.name.endswith(('.gradle','.gradle.kts')) or 'gradle' in p.name.lower():return False
    if p.name in ('config.php','.setup.lock','.env') or p.suffix.lower() in {'.key','.pem','.sql','.db','.sqlite','.zip','.p12','.jks','.log','.gz'}:return False
    if any(x in {'node_modules','.git','storage','backups','.import_tmp'} for x in parts):return False
    if not all(all(c.isalnum() or c in '._-' for c in part) for part in parts):return False
    return True

def create(key_file:Path|None,output:Path)->None:
    if key_file is not None:
        if not key_file.is_file():raise RuntimeError('A chave privada não existe.')
        key_file=key_file.resolve()
        if key_file.is_relative_to(ROOT.resolve()):raise RuntimeError('A chave privada NÃO pode ficar dentro do projeto.')
    version=(ROOT/'VERSION').read_text().strip()
    if not re.fullmatch(r'\d+\.\d+\.\d+',version):raise RuntimeError('VERSION inválida')
    files=sorted((p for p in (ROOT/'app').rglob('*') if allowed(p)),key=lambda p:p.relative_to(ROOT).as_posix())
    files+=sorted((p for p in (ROOT/'public').rglob('*') if allowed(p)),key=lambda p:p.relative_to(ROOT).as_posix())
    files+=[ROOT/'VERSION']
    origin = verified_project_repository()
    virtual_files = {}
    if origin and not (ROOT/'app/update-origin.json').exists():
        virtual_files['app/update-origin.json'] = json.dumps({'repository':origin}, separators=(',', ':')).encode()
    if len(files)+len(virtual_files)>MAX_FILES:raise RuntimeError('Arquivos demais para o motor atual.')
    descriptors=[{'path':p.relative_to(ROOT).as_posix(),'sha256':hashlib.sha256(p.read_bytes()).hexdigest(),'size':p.stat().st_size} for p in files]
    descriptors += [{'path':name,'sha256':hashlib.sha256(data).hexdigest(),'size':len(data)} for name,data in virtual_files.items()]
    # Atualmente schema 36; alterá-lo obriga atualização assistida por segurança.
    migration=(ROOT/'app/schema_migrations.php').read_text()
    found=re.search(r'const\s+IGUA_SCHEMA_REVISION\s*=\s*(\d+)',migration)
    if not found:raise RuntimeError('Revisão do esquema desconhecida')
    manifest={'format':'meu-site-update-v2','version':version,'min_php':'8.1.0','schema_revision':int(found.group(1)),'files':descriptors}
    raw=json.dumps(manifest,ensure_ascii=False,separators=(',',':')).encode()
    sig=None
    if key_file is not None:
        with tempfile.TemporaryDirectory() as tmp:
            f=Path(tmp)/'release.json';f.write_bytes(raw)
            sig=subprocess.run(['php',str(ROOT/'tools/sign_manifest.php'),str(key_file),str(f)],capture_output=True,check=True).stdout
        if len(sig)!=64:raise RuntimeError('Assinatura inválida')
    output=output.resolve(); output.parent.mkdir(parents=True,exist_ok=True)
    if output.is_relative_to(ROOT.resolve()):raise RuntimeError('Saída não deve ficar dentro do projeto.')
    with ZipFile(output,'w',ZIP_DEFLATED,compresslevel=8) as z:
        for name,content in [('release.json',raw)]+([('release.sig',sig)] if sig is not None else []):
            zi=ZipInfo(name);zi.create_system=3;zi.external_attr=(0o100644 << 16);zi.compress_type=ZIP_DEFLATED
            z.writestr(zi,content)
        for file in files:
            zi=ZipInfo(file.relative_to(ROOT).as_posix());zi.create_system=3;zi.external_attr=(0o100644 << 16);zi.compress_type=ZIP_DEFLATED
            z.writestr(zi,file.read_bytes())
        for name,content in virtual_files.items():
            zi=ZipInfo(name);zi.create_system=3;zi.external_attr=(0o100644 << 16);zi.compress_type=ZIP_DEFLATED
            z.writestr(zi,content)
    with ZipFile(output) as z:
        assert z.testzip() is None
        assert len(z.namelist())==len(descriptors)+(2 if sig is not None else 1)
        assert not any(x.startswith('storage/') for x in z.namelist())
    print(f'Atualização v{version} ({"assinada" if sig is not None else "sem assinatura"}): {len(descriptors)} arquivos, {output.stat().st_size} bytes, verificação CRC OK')
    print(output)

if __name__=='__main__':
    parser=argparse.ArgumentParser()
    parser.add_argument('--key-file',type=Path,help='opcional: chave privada Ed25519 externa ao projeto')
    parser.add_argument('--output',required=True,type=Path)
    opts=parser.parse_args();create(opts.key_file,opts.output)
