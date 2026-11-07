# Validação v1.0.48

Alterações: diagnóstico de arquivos públicos, limites multipart, pré-verificação CLI, bloqueio de symlink externo e aviso HTTPS na instalação. Sem novas migrações.

## Testes previstos

`php -l` para todos os PHP; execução de `tests/*.php`; execução de `tests/*.js`; verificações de CSS e JS consolidado; `php tests/runtime_147.php`, `php tests/runtime_148.php` e `php tests/setup_diagnostics.php`; integridade CRC do ZIP. A prova real em Termux e em hospedagem com MariaDB continua pendente.
