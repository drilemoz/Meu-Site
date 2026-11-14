#!/usr/bin/env python3
"""Rotas HTTP reais do PHP local, sem MariaDB. Não valida artigo publicado/301."""
import http.client, pathlib, socket, subprocess, time
root = pathlib.Path(__file__).resolve().parent.parent
with socket.socket() as sock:
    sock.bind(('127.0.0.1', 0)); port = sock.getsockname()[1]
proc = subprocess.Popen(['php','-S',f'127.0.0.1:{port}','-t','public','public/router.php'],
                        cwd=root,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
try:
    def request(path):
        for n in range(40):
            try:
                conn = http.client.HTTPConnection('127.0.0.1',port,timeout=3)
                conn.request('GET',path)
                res=conn.getresponse(); status=res.status; loc=res.getheader('Location')
                res.read();conn.close();return status,loc
            except OSError:
                if n==39:raise
                time.sleep(.1)
    for path,expected in [
        ('/noticia/iguatama-noticias',303),('/categoria/cidade',303),
        ('/noticia/errado!',404),('/categoria/%2e%2e/segredo',404),
        ('/qualquer-rota-inexistente',404),('/%252e%252e/storage/config.php',404),
        ('/assets/css/style.css',200),('/index.php?p=noticia&slug=teste',303),
    ]:
        status,loc=request(path)
        assert status==expected,(path,status,expected)
        if status==303:assert loc=='/setup.php',(path,loc)
        print(f'OK HTTP {status}: {path}')
finally:
    proc.terminate()
    try:proc.wait(timeout=5)
    except subprocess.TimeoutExpired:proc.kill()
