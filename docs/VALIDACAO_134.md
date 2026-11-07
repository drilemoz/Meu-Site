# Validação IguaNews 1.0.34

## Mudanças

- Adicionado ensaio de recuperação de dados em MariaDB real e ZipArchive real: `tests/integration/restore_mariadb_134.php`.
- Novo workflow GitHub Actions `.github/workflows/integration-mariadb.yml` com MariaDB 11.4 descartável, PHP `pdo_mysql` e `zip`.
- Novo teste estrutural de salvaguardas: `tests/integration_guard_134.php`.
- Documentação de execução segura em `docs/INTEGRACAO_MARIADB_134.md`.
- Backup v10 e pacote v2 **sem alterações de formato**. Esquema SQL e marcador de revisão 33 não foram alterados.

## Validação local efetivamente realizada

- `php -l`: **91 arquivos PHP** aprovados, incluindo o novo executor.
- `node --check`: **14 arquivos JavaScript** aprovados.
- `php tests/*.php`: **33 suítes PHP** aprovadas.
- `node tests/*.js`: **5 suítes JavaScript** aprovadas.
- `python3 tests/seo_http_125.py`: **8 cenários HTTP** aprovados (roteador sem instalação).
- `python3 tools/build_css.py --check`: aprovado, CSS consolidado em sincronia.
- Executor de integração sem confirmação: termina em **código 2**, antes de escrever no banco.
- Verificação YAML do workflow: passou.

## Integração ainda pendente

Este ambiente **não possui** serviço MariaDB, extensão `pdo_mysql` nem `ext-zip` no PHP. O ensaio integrado com SQL, ZIP e restauração de mídias não foi executado aqui. O workflow permite a realização desse teste no GitHub Actions após publicar o projeto, mas seus resultados devem ser conferidos no log real antes de declarar aprovação.

## Restrição de segurança

O teste de integração exige `IGUA_TEST_ALLOW_WRITE=YES_ISOLATED_DB`, banco novo com prefixo `igua_ci_`, banco vazio, conexão local e privilégios concedidos somente ao banco de teste. Ele contém operações de restauração que substituem dados do banco configurado; **nunca executar em produção**.
