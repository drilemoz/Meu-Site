# Meu Site v1.0.77 — Correção da integração MariaDB no CI

- Corrigido o teste de login persistente no MariaDB: HTTPS simulado e saída bufferizada no PHP CLI, preservando o bloqueio de cookies sobre HTTP na aplicação.
- Novo teste `tests/remember_cli_headers_177.php` reproduz as condições responsáveis pela falha no GitHub Actions.
- Conferência completa de inventário e identidade: portal PHP, sem Kotlin, Gradle, Android, Uai Cortes ou arquivos privados nos pacotes.
- Documentação centralizada: removidos relatórios históricos e manuais supersedidos; guias atuais indexados em `docs/INDICE.md`.
- `README.md` e `AGENTS.md` simplificados e sincronizados à implementação real da v1.0.77.
- Backup explicado de forma explícita no painel: **Gerar backup agora** = somente banco MySQL; **Baixar pacote completo ZIP** = banco + mídias **referenciadas**. Não é cópia integral da hospedagem.
- Acrescentada auditoria automatizada do empacotamento e da estrutura do projeto, também executada pelo CI.
- Preservados schema MySQL 36, formulários e processos de segurança, autenticação, GA4, Search Console, atualização via modal e backups. Nenhuma alteração de tabelas ou arquivos privados.

**Testes**: regressão PHP/JavaScript e verificações de sintaxe, CSS/editor e pacotes. A restauração MariaDB/ZipArchive na hospedagem, importação no GitHub Manager e interface real do Chrome Android precisam de validação no ambiente de destino.
