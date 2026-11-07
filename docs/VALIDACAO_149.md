# Validação — Meu Site v1.0.49

## Escopo

Limites de 15 MiB para backup de migração, perfil PHP-FPM/CGI (`public/.user.ini`), Termux (`bin/start-termux.sh`), diagnóstico no instalador, pré-validação JS e mensagens de erro. Sem mudança de SQL ou formato de backup.

## Testes locais

- Sintaxe PHP de todos os arquivos de `app/`, `public/`, `bin/` e `tests/`.
- Todas as suítes locais `tests/*.php` e `tests/*.js`.
- `php tests/upload_149.php` e `node tests/setup_backup_upload_149.js` — vários limites de PHP, upload recusado e arquivo máximo de 15 MiB.
- `sh -n bin/start-termux.sh`, `python3 tools/build_css.py --check` e `python3 tools/build_news_editor.py --check`.
- Servidor PHP embutido com `-d upload_max_filesize=20M -d post_max_size=24M`: `GET /setup.php` informa os limites reais; `GET /.user.ini` e `/.htaccess` retornam 404; novo JS público retorna 200.

## Testes ainda necessários em produção

- Instalar/restaurar backup real de ~15 MiB em Termux com MariaDB (e validar backup/restauração de dados, usuários e schema 36).
- Hospedagem PHP-FPM com `.user.ini` copiado corretamente e cache expirado. Confirmar política do provedor sobre overrides, limite do proxy e permissão de upload.
- Hospedagens Apache mod_php, Nginx e painel com limitações próprias; `.user.ini` não funciona em todos.
- Integração completa com MariaDB e teste visual no Android.

## Limitações

O PHP não pode elevar `upload_max_filesize` ou `post_max_size` durante a requisição HTTP. O arquivo `.user.ini` solicita valores quando o SAPI e o provedor permitem; o Termux exige iniciar o **novo processo** pelo script. A validação JavaScript melhora a UX mas não substitui controles PHP. Não confundir capacidade indicada pelo CLI com a capacidade HTTP efetiva.
