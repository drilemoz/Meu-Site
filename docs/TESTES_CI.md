# Testes e integração MariaDB — versão 1.0.79

## Comandos locais

```sh
for f in tests/*.php; do php "$f" || exit 1; done
for f in tests/*.js; do node "$f" || exit 1; done
find app public tests -name '*.php' -type f -print0 | xargs -0 -n1 php -l
python3 tools/build_css.py --check
python3 tools/build_news_editor.py --check
python3 tools/audit_project.py
```

## GitHub Actions

O workflow `.github/workflows/integration-mariadb.yml` configura MariaDB 11.4 e instala `php-cli php-mysql php-zip`, executa testes, testa uploads segmentados e restauração em base isolada. **Não** execute os scripts de restauração no banco de produção.

`tests/fixtures/backup133_duplicate_names.zip` é um ZIP deliberadamente inválido, com nomes repetidos, criado para provar que o restaurador rejeita entradas duplicadas. Ele pertence **somente aos testes**, nunca ao pacote de atualização da hospedagem.

## Limites

Este ambiente pode não oferecer `pdo_mysql`/`ZipArchive`, cURL ou acesso à hospedagem. Testes em stubs/sintaxe não equivalem a executar restauração real no MariaDB, observar o modal no Chrome Android ou comprovar a recepção de dados pelo GA4.

Os pacotes de código-fonte devem conter somente este portal PHP; nenhuma entrada Kotlin, Gradle, Android ou outro projeto.

## Falha do workflow de 07/11/2026 (corrigida na v1.0.78)

O teste `tests/integration/restore_mariadb_134.php` completou a integração MariaDB/ZipArchive, inclusive round-trip de backup de banco e mídia, rollback e rejeição de pacote adulterado. O workflow falhou **depois**, em `remember_mariadb_140.php`, porque o CLI não fornecia HTTPS e os diagnósticos anteriores já tinham enviado saída antes de `setcookie()`. O teste agora simula HTTPS e bufferiza a saída. A segurança da emissão do cookie no portal não foi relaxada.

Para validar a correção sem banco: `php tests/remember_cli_headers_177.php`. Para confirmação de integração real, reexecutar o workflow no GitHub Actions com MariaDB isolado; a execução local não prova a compatibilidade do ambiente de produção.

## Falha do workflow de 07/11/2026 (corrigida no código v1.0.78)

O teste de login persistente passou. A etapa MariaDB foi interrompida posteriormente em `tests/integration/elections_mariadb_141.php` porque a função `igua_schema_revision()` de `app/schema_migrations.php` não havia sido importada explicitamente. A correção adiciona o `require_once` faltante; `tests/integration_bootstrap_178.php` detecta futuras regressões sem banco. O teste de prévia da Central de Integrações também recebeu seus dados sintéticos completos. Resta confirmar a execução real no GitHub Actions.

## Falha do workflow #6 (corrigida no código v1.0.79)

O workflow confirmou **backup e restauração com MariaDB + ZipArchive real**, login persistente e eleições. A falha final ocorreu em `tests/integration/news_indexes_mariadb_146.php`: a simulação de revisão 35→36 chamava `migrate_ads_schema()` sem carregar `app/ads.php`. O mesmo cenário exigia também módulos do editor e métricas.

O teste agora importa explicitamente os módulos de Publicidade, Editor, Métricas, sessão persistente e Eleições e checa as funções antes de `ALTER TABLE articles DROP INDEX...` (DDL com auto-commit). `php tests/integration_bootstrap_179.php` valida esses imports sem alterar um banco. Resta reexecutar o workflow para confirmar a migração e os cinco padrões EXPLAIN com MariaDB real. Nunca executar os testes de integração num banco da hospedagem.
