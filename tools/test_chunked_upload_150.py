#!/usr/bin/env python3
"""HTTP real com PHP upload_max_filesize=2M, post_max_size=8M. Sem MariaDB."""
import hashlib, http.cookiejar, json, os, pathlib, socket, subprocess, sys, time, urllib.request, urllib.error
from urllib.parse import urlencode
root = pathlib.Path(__file__).resolve().parents[1]
with socket.socket() as sock:
    sock.bind(('127.0.0.1', 0))
    port = sock.getsockname()[1]
server = subprocess.Popen(['php','-d','upload_max_filesize=2M','-d','post_max_size=8M','-S',f'127.0.0.1:{port}',
                           '-t',str(root/'tests/integration')],cwd=root, stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
try:
    jar = http.cookiejar.CookieJar()
    op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    url = f'http://127.0.0.1:{port}/chunked_upload_fixture_150.php'
    for i in range(70):
        try:
            init = json.load(op.open(url,timeout=1));break
        except Exception:
            if server.poll() is not None:raise RuntimeError('PHP HTTP encerrou inesperadamente')
            time.sleep(.07)
    else:raise RuntimeError('Servidor PHP HTTP não iniciou')
    assert init['chunk_size'] == 524288
    csrf = init['csrf']
    raw = os.urandom(15*1048576 if '--full' in sys.argv else 2*1048576+2000)
    ident = ''
    for offset in range(0,len(raw),524288):
        data = raw[offset:offset+524288]
        boundary='check150boundary'
        fields = {'csrf':csrf,'backup_name':'teste.json.gz','upload_total':str(len(raw)),
                  'upload_offset':str(offset),'upload_token':ident}
        chunks=[]
        for key,value in fields.items():
            chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
        chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="chunk"; filename="parte.bin"\r\nContent-Type: application/octet-stream\r\n\r\n'.encode()+data+b'\r\n')
        chunks.append(f'--{boundary}--\r\n'.encode())
        req = urllib.request.Request(url,data=b''.join(chunks),headers={'Content-Type':f'multipart/form-data; boundary={boundary}'})
        try:
            response = json.load(op.open(req,timeout=10))
        except urllib.error.HTTPError as e:
            raise RuntimeError('HTTP upload status '+str(e.code)+': '+e.read().decode()) from e
        assert response['ok'] and response['next_offset']==offset+len(data),response
        ident = response['upload_token']
    assert response['complete']
    inspect=urllib.request.Request(url,data=urlencode({'csrf':csrf,'action':'inspect'}).encode())
    result=json.load(op.open(inspect,timeout=10))
    assert result['size']==len(raw), result
    assert result['sha256']==hashlib.sha256(raw).hexdigest(),result
    print(f'OK: upload real HTTP 2M/8M, {len(raw):,} bytes, blocos 512 KiB, SHA-256, sessão e limpeza')
finally:
    server.terminate()
    try:server.wait(timeout=3)
    except subprocess.TimeoutExpired:server.kill()
