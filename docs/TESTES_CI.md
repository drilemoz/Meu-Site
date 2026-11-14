# Testes e integração MariaDB — versão 1.0.88

## Comandos locais

```sh
for f in tests/*.php; do php "$f" || exit 1; done
for f in tests/*.js; do node "$f" || exit 1; done
find app public tests -name '*.php' -type f -print0 | xargs -0 -n1 php -l
python3 tools/build_css.py --check
python3 tools/build_news_editor.py --check
python3 tools/audit_project.py
```

Testes de integrações: `php tests/pagespeed_181.php` (transportes simulados, validação de URL pública, erros e expiração), `php tests/pagespeed_182.php` (estratégias mobile/desktop, quatro categorias, cache isolado e retrocompatibilidade), `php tests/pagespeed_183.php` (notas, recomendações condicionadas e interface), `node tests/integrations_181.js` (navegação e UX) e `node tests/integrations_182.js` (progresso separado de cada formulário). A API PageSpeed externa **não** é consultada pelos testes automáticos, evitando cotas, rede variável e falsos positivos.

## AdSense Management API (v1.0.87)

`php tests/adsense_oauth_187.php` valida URL pública fixa, cliente OAuth, state/PKCE, expiração, callback de uso único, refresh token, verificação da conta associada ao ca-pub, métricas com moeda, cache, ACL/CSRF e sigilo no HTML. O transporte da API é simulado, sem autenticação real ou uso de quotas. O teste precisa manter `/tmp` com permissão compartilhada, sem tentar alterá-la. A autorização verdadeira e o callback só podem ser comprovados na hospedagem HTTPS com conta Google real.

## GitHub Actions

O workflow `.github/workflows/integration-mariadb.yml` configura MariaDB 11.4 e instala `php-cli php-mysql php-zip`, executa testes, testa uploads segmentados e restauração em base isolada. **Não** execute os scripts de restauração no banco de produção.

`tests/fixtures/backup133_duplicate_names.zip` é um ZIP deliberadamente inválido, com nomes repetidos, criado para provar que o restaurador rejeita entradas duplicadas. Ele pertence **somente aos testes**, nunca ao pacote de atualização da hospedagem.

## Publicação automática de Releases

Em qualquer push para `main` que altere o código, versão, documentação ou o próprio workflow, o job `integration` testa PHP, JS, CSS e MariaDB. O job `publicar_release` só executa **depois do sucesso** e publica somente quando `VERSION` mudou em relação ao commit anterior, com permissão temporária `contents: write` obtida do `GITHUB_TOKEN` do Actions. Não há PAT.

O job lê o arquivo `VERSION` (X.Y.Z), procura a Release/tag `vX.Y.Z` e:

- se já existir Release com `Atualizacao-vX.Y.Z.zip`, não recria nem substitui;
- se a tag existir sem a Release ou a Release estiver incompleta, aborta para inspeção manual;
- se a versão for inferior ou igual à última Release publicada, não publica;
- caso contrário, gera o pacote v2, verifica `release.json`/SHA-256 e publica o ZIP **daquele commit validado**.

Execução manual (`workflow_dispatch`) somente executa os testes, sem publicar; publicação ocorre em pushes `main`. Para a próxima entrega, incremente `VERSION` e `github-manager.json`/`app_identity.json` juntos. O GitHub Manager não precisa ter a função de criar Release para projetos PHP.

**Limites:** se GitHub Actions não conceder `contents: write`, o job falhará; revisar as permissões do repositório. SHA-256 no ZIP garante integridade interna, não identidade criptográfica de origem. A instalação no painel exige senha/confirmacão; não há instalação silenciosa.

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

## Search Console real (v1.0.84)

`tests/search_console_api_184.php` e `tests/search_console_api_184.js` cobrem JWT OAuth com escopo `webmasters.readonly`, propriedade autorizada, URLs permitidas, datas, relatórios, escaping/CSRF e andamento visual. Os dados são simulados, sem realizar requisição externa nem expor a conta de serviço. A validação real requer PHP cURL/OpenSSL, Search Console API habilitada e permissão da conta de serviço na propriedade.

## Inspeção individual Search Console (v1.0.85)

`tests/search_console_inspection_185.php` e `tests/search_console_inspection_185.js` validam o novo fluxo: origem URL do banco, rejeição de rascunhos/demonstrações/URLs arbitrárias, OAuth somente leitura e propriedade autorizada, status indexado/excluído/erro/desconhecido, cache limitado, sem segredos no HTML e indicador de carregamento. Dados de API e RSA são simulados. Não substitui requisição real nem browser Android.

## Diagnóstico de integrações (v1.0.88)

`php tests/integration_diagnostics_188.php` exercita o mapeamento de serviços, classificação segura de erros, limite de eventos, estado local versus teste externo, histórico de sessão e renderização do painel sem credenciais ou rede. A cobertura não substitui testes de APIs reais ou testes visuais em Android.

## Regressão da Central em janelas (v1.0.89)

Executar `php tests/integrations_modal_189.php` e `node tests/integrations_modal_189.js` para testar abas, navegação de diálogos, preservação de controles e documentação de APIs. A cobertura usa DOM simulado no Node e inspeção estrutural no PHP; confirmar visual e navegação por teclado/Android em navegador real antes de produção.

## Falha no GitHub Actions de 07/11/2026 (correção de código v1.0.95)

O runner Ubuntu 24.04 com PHP 8.3 e MariaDB 11.4 concluiu o checkout e instalou `pdo_mysql` e `zip`. A etapa **Sintaxe e regressão PHP** interrompeu em `tests/news_wizard_194.php`, ao passar `invalid` para `igua_news_relative_date()` em `app/admin_views/partials/news_cards.php`. A construção por `new DateTimeImmutable()` disparou `DateMalformedStringException` e erro fatal secundário ao manipular `xdebug_message`.

A v1.0.95 utiliza `date_create_immutable()`, que retorna `false` para entrada inválida, verifica avisos de parsing e informa “Data indisponível”. A nova regressão `tests/news_relative_date_195.php` cobre tanto datas válidas quanto inválidas, impossíveis, vazias e nulas.

**Verificação pendente:** enviar a v1.0.95 para `main` e aguardar novo workflow. O log recebido não contém execução dos testes integrados de restauração de banco e mídias, portanto nenhum resultado deve ser atribuído a esses testes.


## Módulo Aplicativos e Páginas Legais (v1.0.96)

O teste offline `tests/legal_apps_196.php` verifica slugs, reservados, renderização Markdown sem HTML executável, rotas públicas e autorização estrutural. O CI `integration-mariadb.yml` ganhou `tests/integration/legal_apps_mariadb_196.php` para exercitar **dois aplicativos reais**, rascunho versus publicação, revisões, slugs antigos e round-trip de backup v12 em MariaDB isolado. O teste requer opt-in `IGUA_TEST_ALLOW_WRITE=YES_ISOLATED_DB`, banco local de nome `igua_ci_*` e `pdo_mysql`. Não confundir sua inclusão no workflow com resultado aprovado: precisa de execução nova no GitHub Actions.

## Refatoração 1.0.111

Executar `php tests/refactor_211.php` e `node tests/refactor_211.js`. O teste JavaScript usa DOM simulado para validar prévias, mas não substitui a inspeção visual real no navegador Android.

## Refatoração de integrações (v1.0.112)

Executar `php tests/integrations_refactor_212.php` além da suíte completa. Os testes que analisam texto-fonte de `integrations.php` usam o auxiliar `tests/support/integrations_source.php` para recompor os blocos da view. Isso preserva validações de IDs, guias, CSRF, formulários, rótulos, modal e documentação após a divisão em componentes. A execução do template também é conferida em testes existentes como `tests/render_integrations_169.php` e `tests/integrations_169.php`.

## Ciclo integrado de atualização — versão 1.0.117

O CI agora executa `tests/integration/updater_mariadb_217.php` com as extensões reais `pdo_mysql` e `ZipArchive`, depois de preparar o banco isolado com `restore_mariadb_134.php`. O ensaio instala e restaura **somente código fictício na pasta temporária**, gera backup SQL lógico no armazenamento temporário e recusa um ZIP adulterado, sem restauração ou exclusão de banco. O teste de contrato `tests/updater_ci_contract_217.php` funciona offline e verifica o isolamento e a ordem de execução.

Os testes locais não conseguem executar esse cenário quando as extensões MariaDB e ZIP não estão instaladas. Consulte [Integração do atualizador 1.0.117](INTEGRACAO_ATUALIZADOR_117.md) para a matriz de verificação e as limitações de homologação.

## Correção do teste de usuário MySQL local (v1.0.135)

O workflow 47 concluiu os testes de backup/restore, mas falhou no provisionamento dedicado (`1045`): o runner atravessou a ponte Docker e o MariaDB enxergou `172.18.0.1`, fora dos hosts locais permitidos. O passo de provisionamento agora usa somente o namespace **de rede** do contêiner MariaDB (`job.services.mariadb.id` / `docker inspect` / `sudo nsenter --net`), valida `SELECT USER()` como loopback e não altera `GRANT`. O teste do atualizador permanece na etapa seguinte. Teste estático `tests/ci_mysql_local_235.php`; nova execução MariaDB real ainda pendente. Consulte [CI_MARIADB_LOCAL_135.md](CI_MARIADB_LOCAL_135.md).
