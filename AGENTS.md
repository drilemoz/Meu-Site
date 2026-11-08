# Diretrizes de manutenção — Meu Site (v1.0.115)

Este repositório contém **somente o portal PHP/MySQL Meu Site**. Antes de editar código, ler `README.md`, `docs/INDICE.md`, o módulo afetado e os testes.

## Regras de projeto e versão

1. PHP 8.1+, MySQL/MariaDB, projeto web. Nenhum Kotlin, Android, Gradle, Uai Cortes, APK, design/ de outros projetos ou arquivos privados no ZIP.
2. Cada mudança entregue incrementa `VERSION` sequencialmente e sincroniza `README.md`, `CHANGELOG.md`, `RELEASE_NOTES.md`, `github-manager.json` e `app_identity.json`.
3. Gerar os arquivos com `tools/build_source_package.py` e `tools/build_update_package.py`. O ZIP de hospedagem deve conter somente `app/`, `public/` e `VERSION`, além de `release.json`/assinatura opcional; jamais `storage/`, testes, `.git`, arquivos de configuração privados ou uploads.
4. A versão de schema atual é 37. O atualizador normal **não aceita migração de schema**: planejar uma instalação assistida antes de mudar tabelas, migrações ou formato de backup. Preservar restauração de backups antigos.
5. O pipeline de publicação da Release executa apenas após a integração MariaDB/JavaScript passar em `main`. Verificar que o tag `vX.Y.Z` ainda não existe e publicar somente o ZIP v2, nunca código-fonte ou segredos. A publicação é automática, mas a instalação requer confirmação administrativa.
6. O repositório em `app/update-origin.json` é `drilemoz/Meu-Site`, com versões e identidade correspondentes. GitHub Manager com suporte a projeto PHP depende de teste no aplicativo, não presumir compilação Android.

## Segurança e dados

- Preservar autenticação, ACL (admin/editor/redator), CSRF, senha administrativa para operações críticas, proteção contra autodesativação, SQL preparado, escaping e validação de uploads.
- `storage/` é privado e persistente na hospedagem: config, banco lógico, logs, eventos de segurança e chave de conta de serviço GA4. Não expor chaves, JWT, cookies, conteúdo de requisições, senhas ou segredos no ZIP, HTML ou logs.
- Atualizador ZIP v2 aceita ausência de assinatura, mas SHA-256 do manifesto não comprova origem. Preservar limites de arquivos, versões, journal, backups e rollback; em timeout nunca reenviar POST automaticamente.
- O backup lógico JSON/GZIP contém **somente tabelas MySQL**; o pacote completo ZIP também contém apenas mídias *referenciadas*. Não apagar uploads órfãos por acidente. Ver `docs/BACKUP.md`.
- Na restauração, verificar integridade de nomes duplicados, hashes, caminhos, limites e manter ao menos um administrador ativo. Proteger o banco e arquivos com cópias externas antes de operações críticas.
- Eventos da Central de Segurança são parciais do PHP; não apresentar como captura universal de ataques. Bloqueios de IP temporários e administrativos devem evitar o IP atual.

- O AdSense 1.0.87 exige OAuth2 do proprietário, não conta de serviço. Não misturar refresh token e chave GA4. `storage/integrations/adsense-oauth.json` permanece privado; state + PKCE e callback fixo no domínio HTTPS, tokens nunca em sessão/HTML.

- Diagnóstico 1.0.88: somente sessões de administrador, até 12 eventos genéricos, sem persistir segredos, corpo da resposta, tokens ou URL OAuth. Configuração local não implica autorização; êxito de chamada não comprova presença de eventos ou aprovação Google.

## Front-end e integrações

- Design mobile-first compacto, ícone `?` para ajuda opcional. Avisos importantes sobre perda de dados, privacidade e erros permanecem **visíveis**. Alvos de toque ~44 px e boa leitura no celular.
- O menu do painel é um drawer à direita, estreito (248 px no celular, 270 px demais telas). Biblioteca de mídia integrada ao admin sem perder cabeçalho/rodapé; seletor de fotos pode usar modal.
- CSS é fonte modular (17 módulos); alterar `public/assets/css/modules/` e executar `tools/build_css.py`. Editor JS usa 8 módulos; alterar fonte e executar `tools/build_news_editor.py`.
- AdSense e GA4 têm IDs em configuração **única** na Central de Integrações; campos antigos não podem apagar esses valores. Publicidade e analytics próprios mantêm seus switches independentes.
- GA4 só carrega no público quando configurado e após consentimento; respeitar GPC/DNT e revogação. Chaves de serviço Google ficam em `storage/integrations/` e a consulta à API é sob demanda com autorização administrativa.
- O GA4 1.0.87 consulta relatório de audiência apenas sob demanda por administradores, usando `batchRunReports` (quatro relatórios em uma chamada); OAuth somente leitura, propriedade validada, cache em sessão por até uma hora e limites entre tentativas, sem persistir tokens. Dados GA4 de propriedade não se confundem com métricas internas nem com o fluxo específico do portal.
- O PageSpeed Insights oferece consultas HTTPS externas para celular e computador, independentes e somente sob ação do administrador, apenas para o domínio público salvo em SEO, sem segredo armazenado e com cache/limites de consulta por dispositivo. As notas Lighthouse são de laboratório, não tráfego real.
- Search Console DNS TXT/metatag e inspeção de páginas são verificações **locais**. A Search Console API 1.0.84 tem consulta **real, sob demanda**, somente leitura, com OAuth da conta de serviço Google compartilhada com GA4, permissão explícita da propriedade e URL derivada de SEO. Na 1.0.85 a URL Inspection API inspeciona **somente URLs canônicas de notícias publicadas**, derivadas do banco, com autorização de leitura e cache restrito à notícia/propriedade. Não promete teste ao vivo nem solicita indexação. Não expor JWT/token/credencial; usar endpoints Google fixos e expirar cache em sessão. Não habilitar HTTP remoto arbitrário/SSRF.
- Preservar dados reais e marcar conteúdo de demonstração. Horários de ônibus não devem ser inventados; fontes e notícias exigem revisão humana. Não modificar dados reais durante inspeções de SEO.

- Na Central 1.0.89, preservar `<dialog>` e navegação acessível: abas, `?`, formulários movidos (não clonados), links profundos, botões Voltar e Fechar, proteção CSRF e scripts gerais do painel. Não voltar a abrir cartões rolando a página.

## Ajustes do wizard (1.0.91)

- A recuperação do mesmo rascunho é apresentada uma única vez por aba/sessão após clicar em Recuperar ou Ignorar; upload de mídia não entra no autosave.
- Cadastro de categoria/fonte dentro da notícia exige `manage_taxonomy` e proteção CSRF no backend. A notícia preserva somente uma fonte principal por enquanto; nenhuma migração de banco.
- Novas matérias não exibem cartões estruturados; edição de notícias que já usam blocos continua permitida. SEO detalhado abre diálogo nativo com dados locais, nunca chamado ao Gemini.
- Anexos seguros usam allowlist de extensões/MIME, ficam fora da pasta pública e são baixados com Content-Disposition: attachment. Alterar extensões exige manter `news_attachment_storage_name_valid` e backup/restauração compatíveis.
- O Google Drive deve guardar apenas os dois ZIPs da versão vigente (fonte e atualização) após conferência dos uploads.

## Atualizações pelo GitHub (1.0.92)

- O botão Visualizar abre o diálogo de consulta GitHub via GET autenticado, sem publicar o link Releases nem os cartões de origem e servidor na tela principal.
- A instalação remota permanece POST com CSRF, confirmação de origem, senha administrativa, limites e rollback. Apenas as etapas confirmadas pelo backend são apresentadas ao longo do processamento: consulta, download, validação, preflight, backup e aplicação. Sem percentuais falsos.
- Progresso é registrado no armazenamento privado, com token aleatório por operação e vínculo ao ID do administrador; GET de status exige sessão e `manage_settings`. A sessão do POST remoto é temporariamente liberada para leitura paralela e reaberta ao final para flash/erro.
- Ao confirmar sucesso no retorno PHP, o modal apresenta Atualização concluída e Atualizar página. Timeout/conexão perdida nunca gera reenvio automático. Outros diálogos de origem e vistoria continuam acessíveis apenas nas opções avançadas.
- Testes de regressão: `tests/updater_github_192.php` e `tests/updater_github_192.js` mais toda a suíte. O schema permanece 36.

## Wizard de notícias (1.0.90)

- Cadastro com 9 passos e revisão final; preservar nomes de campos, ACL, CSRF e validações no controlador.
- Arquivo ZIP de fotos só é aceito com ZipArchive, até 10 imagens e limites explícitos; nunca extrair nomes/caminhos do ZIP diretamente. A importação ocorre na mesma transação da notícia.
- Capa sugerida a partir da primeira imagem se não houver outra escolhida. Fotos e arquivos locais só são persistidos após salvar; autosave textual não guarda uploads.
- Publicação escolhida somente no último passo; validar modo e agendamento também no servidor. Schema continua 36, sem migração.

## Testes obrigatórios

Executar antes de empacotar, incluindo `tests/integrations_modal_189.php` e `tests/integrations_modal_189.js`, `tests/integration_diagnostics_188.php`, `tests/ga4_audience_186.php`, `tests/search_console_api_184.php`, `tests/search_console_api_184.js`, `tests/search_console_inspection_185.php`, `tests/search_console_inspection_185.js`, `tests/pagespeed_181.php`, `tests/pagespeed_182.php`, `tests/integrations_181.js`, `tests/integrations_182.js`, `tests/remember_cli_headers_177.php`, `tests/integration_bootstrap_178.php` e `tests/integration_bootstrap_179.php` e `tests/release_workflow_180.js` para prevenir a regressão de HTTPS/headers no CLI:

```sh
for x in tests/*.php; do php "$x" || exit 1; done
for x in tests/*.js; do node "$x" || exit 1; done
find app public tests -name '*.php' -type f -print0 | xargs -0 -n1 php -l
python3 tools/build_css.py --check
python3 tools/build_news_editor.py --check
python3 tools/audit_project.py
```

Verificar CRC, conteúdo, manifesto SHA-256, ausência de segredos e arquivos de outro projeto e compatibilidade do pacote v2. A integração real exige PHP + `pdo_mysql` + `ZipArchive` com MariaDB isolado e teste em navegador; não declarar produção validada sem evidência. Documentar limitações em `docs/AUDITORIA_176.md` e notas de release.

## Componentes de pop-up (1.0.94)

- Para novos diálogos administrativos de conteúdo, usar `<dialog class="igua-modal igua-modal--narrow|--medium|--wide">`. O CSS compartilhado está ao fim de `public/assets/css/modules/16-security.css` e o JS genérico em `public/assets/js/admin-dialogs.js`, carregado no início do painel. Menu de navegação e seletor de mídia não são janelas de conteúdo e preservam comportamento específico.
- Ao abrir/fechar, preferir `window.IguaAdminModal.open(dialog, opener)`/`.close(dialog)` com fallback nativo. Proteger fechamento durante operações marcadas como ocupadas. Jamais clonar formulários de integração ou suprimir CSRF/permissões.
- PageSpeed permite trocar apenas o painel visível com `data-int-speed-tab` e `data-int-speed-panel`; a troca não dispara requisições, portanto testes permanecem sob demanda.
- Regredir em navegador desktop e mobile: dimensões do pop-up, foco, teclado, fechamento Escape, rolagem interna, abas e formulários.

## Google Drive

Ao enviar nova versão de **Meu Site** para `Projetos/Meu Site`, confirmar os **dois** ZIPs novos antes de excluir os anteriores, e verificar que a pasta contém somente a versão atual. Nunca apagar arquivos de outros projetos.

## Correções editoriais e listas (1.0.94)

- inputs de arquivo dentro de `.news-upload-tile` são seletores acessíveis compactos; `file-inputs.js` não deve criar `.igua-file-picker` dentro deles. Os outros formulários preservam o botão global.
- `seo-audit.js` usa campos persistidos `url_slug`, `seo_title`, `seo_description` do formulário e cópias temporárias nos pop-ups; cancelar não altera dados, salvar apenas modifica o rascunho. Não misturar dados de IA ou publicar automaticamente.
- `app/admin_views/partials/news_cards.php` desenha os cartões nas telas Notícias e Visão geral; mantém CSRF, ACL de exclusão/publicação e data relativa no fuso de São Paulo.
- Na edição do painel inicial, preservar permissões e garantir leitura das datas verdadeiras do banco, nunca datas artificiais.

## Regressão de datas no GitHub Actions (1.0.95)

- `igua_news_relative_date()` não deve lançar `DateMalformedStringException` para datas inválidas. `date_create_immutable()` retorna `false` e a função mostra “Data indisponível”; avisos de data impossível também são rejeitados.
- Validar `tests/news_relative_date_195.php` **e** `tests/news_wizard_194.php` no PHP 8.3 do GitHub Actions.
- O log do workflow `integration-mariadb.yml` de 07/11/2026 falhou antes dos testes MariaDB reais; não alegar êxito desses testes antes de novo run aprovado.

## Aplicativos e Páginas Legais (v1.0.96)

- Módulo administrativo restrito a administradores em `app/admin_controllers/apps_legal.php` e `app/admin_views/routes/legal_apps.php`; mantém ACL/CSRF do painel.
- `app/apps_legal.php` detém validação de slug, Markdown escapado, seed de rascunho, tabela, revisões e consultas públicas. Não escrever declarações jurídicas estáticas nem publicar modelos `PREENCHER`.
- URLs públicas dinâmicas no roteador existente, sem login para conteúdo publicado. `legal_app_pages` mantém rascunho e referência à revisão imutável; restaurar não publica.
- Schema 37 é uma migração **aditiva e assistida**; o instalador ZIP v2 comum não atravessa schema 36→37. Backup lógico v12 precisa preservar v1–v11 e as novas FKs, com `storage`/mídias protegidos.
- Testar `tests/legal_apps_196.php` e `tests/integration/legal_apps_mariadb_196.php` (CI com banco isolado), além de toda a regressão. Documentação: `docs/APLICATIVOS_LEGAIS.md`.

## Ajustes 1.0.97

- Janelas de atualização GitHub e ZIP mantêm altura conforme o conteúdo, centralização e rolagem interna, inclusive no celular.
- Consulta GitHub devolve códigos de diagnóstico sanitizados; nunca registrar/responder URL completa de requisição, tokens ou dados privados.
- `diagnose_zip` é POST autenticado com CSRF que analisa manifestos, schema, assinatura/hash e preflight, sem instalar, sem senha e sem modificar o banco. Instalar continua exigindo senha e confirmação; schema 36→37 permanece EXCLUSIVAMENTE assistido.
- Modelos de Uai Cortes são rascunhos; campos `PREENCHER:` impedem publicação jurídica acidental. Aplicação nos rascunhos anteriores exige clique explícito, não sobrescreve conteúdo alterado ou revisões públicas.

## Central de Sitemap e Indexação (v1.0.100)

- `app/sitemap_catalog.php` é a fonte comum do XML `public/sitemap.php` e da listagem privada. Excluir rascunhos, agendamentos e conteúdo demonstrativo. Não alterar as URLs canônicas ou efetuar consultas HTTP automáticas em lote.
- `admin.php?action=sitemap` exige `manage_settings`, oferece busca/filtro/paginação, cópia, testes HTTP individuais e verificação sob demanda de sitemap, robots e ads. Os testes HTTP ocorrem no navegador na mesma origem; **não são consultas Search Console nem garantem indexação**.
- `public/robots.txt` declara `https://iguatama.online/sitemap.php` (domínio oficial deste portal). Ao alterar o domínio canônico da instalação, atualizar a diretiva de maneira explícita. `public/ads.txt` é persistente e gerado na hospedagem, não empacotado nem sobrescrito.
- XML limitado a 50.000 URLs totais. A central alerta sobre excesso; antes de ultrapassar esse limite em produção, implementar sitemap index/fragmentação, sem descartar URLs silenciosamente. Nenhuma nova tabela ou migração, schema 37/backup v12 preservados.

## Correções de taxonomia e destaque (1.0.102)

- Ao adicionar ações AJAX ao controlador, registrá-las tanto em `app/admin_controllers/post_routes.php` quanto na lista permitida em `public/admin.php`. Preservar CSRF, ACL `manage_taxonomy` e JSON seguro.
- A chamada principal da home só pode consultar notícias publicadas marcadas `featured=1`; sem marcações, usar área neutra, nunca promover a mais recente automaticamente. O EXPLAIN deve refletir esse filtro.


## Integração Gemini (1.0.103)

- Reutilizar o card Google Gemini da Central de Integrações; chave privada em `storage/integrations/gemini-api.json`, sem HTML, logs, URLs, GitHub ou backup lógico. Senha administrativa obrigatória para salvar/remover; HTTPS, CSRF e ACL mantidos.
- `integrations_gemini_test` usa chamada `generateContent` real e **manual**; modelo é escolhido pelo administrador, com nome validado e sem URL externa arbitrária. Erros HTTP devem ser tratados com orientação sanitizada e sem resposta bruta.
- Ícones ✦ no wizard não exibem rótulo visual; manter `aria-label`/`title` e área de toque ≥44px. `news_gemini_suggest` exige `write`, limites e cooldown, apenas solicita texto sob clique explícito; exibir para revisão, nunca aplicar sem aprovação e nunca publicar automaticamente.
- Sem schema novo (37/backup v12). Testes offline não comprovam conectividade da API ou conta do proprietário; executar teste em hospedagem com chave real após instalar.

## Viação Teixeira e diagnóstico Gemini (v1.0.105)

- As 10 linhas e 31 grupos de partidas fotografados em 14/09/2026 ficam disponíveis para **importação explícita** em Ônibus, após confirmação do administrador. Não inserir registros ao instalar código ou ao carregar o painel; preservar linhas existentes e evitar duplicação pelo `source_key` e identificação manual. Sem chegada/tarifa inventadas.
- A escala semanal não diferencia feriados que caiam em dias úteis: informar essa limitação sem alegar substituição automática pela escala de sábado/domingo.
- Gemini é serviço Google funcional, não serviço de Catálogo. O teste manual abre diálogo pequeno e entrega diagnóstico JSON sanitizado em POST CSRF, sem chave nem resposta bruta. 503 indica provável sobrecarga externa, sem implicar erro de credencial; respeitar cooldown e limitar tentativas.
- Schema 37/backup v12 mantidos. Reexecutar testes `tests/bus_teixeira_204.php`, `tests/gemini_204.php` e `tests/gemini_204.js` junto à regressão total.


## Integração IndexNow — v1.0.105

Na Central de Sitemap e Indexação, o administrador pode gerar uma chave de domínio com arquivo público e enviar manualmente até dez URLs de notícias ou páginas legais que já constem do sitemap. A configuração é registrada fora da pasta pública, a API é chamada apenas por clique, com CSRF, ACL e intervalo entre envios. O Google não participa do IndexNow. A chamada HTTP 200/202 indica recebimento, não indexação. A hospedagem deve permitir criar o arquivo TXT na raiz pública. Nenhuma notificação é disparada ao publicar sem ação administrativa.

## Atualização 1.0.106

- Confirmar importação de ônibus Teixeira pelo botão de submit (não no form). Qualquer `data-confirm` em form deve prosseguir via `requestSubmit`, nunca `form.click`. O CI executa teste MariaDB isolado com dados temporários, sem modificar produção.
- Bing Webmaster é consulta sob demanda de propriedade/sitemaps com chave privada em `storage/integrations/bing-webmaster.json`, senha e CSRF para configurar/remover. Não duplicar envios IndexNow nem confundir sitemap conhecido com URLs indexadas.

## Atualização 1.0.107

- O menu administrativo usa o rótulo compacto **Páginas**, sem alterar as ações, o título completo da área ou suas permissões.
- A verificação do Bing usa o XML original disponibilizado pelo proprietário em `public/BingSiteAuth.xml`, servido **estaticamente** em `/BingSiteAuth.xml` (HTTP 200 após deploy com docroot público correto). Não é um segredo, chave de API ou IndexNow. O arquivo é parte do pacote de atualização e da fonte, sem geração posterior nem escrita em `storage/`. Conferir a URL pública e tocar em Verify no Bing.
- A lista administrativa de ônibus usa cartões responsivos em vez da tabela com rolagem lateral, filtro GET por cidade de origem lida da primeira parada e botão Editar percurso que abre o wizard existente. Nenhum registro existente é alterado, e os formulários administrativos/CSRF para fixar linhas permanecem intactos.
- Schema 37, backup v12 preservados. Executar `tests/bus_bing_207.php` e a suíte completa antes de empacotar.


## Atualização 1.0.108

- Central de Sitemap tem uma única lista principal de URLs; diagnósticos locais/HTTP, IndexNow e Bing ficam nos diálogos `sitemap-check-dialog`, `sitemap-indexnow-dialog` e `sitemap-bing-dialog`, abertos por `data-sitemap-open` via `IguaAdminModal`. Preservar o acesso por hash `#indexnow`/`#bing-webmaster` e retorno de foco.
- Não remover CSRF, senha, ações POST nem sinais de erro dentro dos pop-ups. Testes de HTTP e chamadas Bing/IndexNow seguem somente sob ação do administrador.
- A listagem exibe caminho curto, mas Abrir/Copiar/Testar mantêm destino completo e checagem de mesma origem. Paginação em 12 por página.
- Contato do desenvolvedor mantém telefone/e-mail e oferece link externo HTTPS com `noopener noreferrer`.
- Executar `tests/sitemap_layout_208.php`, regressão PHP/JS completa, verificação CSS/editor, auditoria e integridade ZIP antes de distribuir. Schema 37 sem migrações.

## Atualização 1.0.110

- Imagem social/favicon são selecionados por biblioteca de mídia e campos de formulário ocultos; não remover validação de MIME/URL/registro de mídia no PHP. Após salvar, o caminho continua na configuração, não texto digitado no modal.
- SEO permite `seo_page_description_{onibus,sobre,contato,anuncie,privacidade,termos}` via settings existentes, sem schema novo. Prioridade: descrição individual preenchida, fallback anterior se vazia. Nunca sobrescrever descrições de artigos e páginas legais.
- Métricas não podem chamar `is_bot=0` de prova de pessoa humana; rotular como acesso não classificado e identificadores estimados. Detector de volume é heurístico e pode ter falsos positivos. Não reclassificar eventos antigos, não armazenar IP/UA brutos e preservar GPC/DNT.
- Rotas amigáveis registradas apenas em padrões conhecidos ou contexto validado de páginas legais, sem registrar query privada. Executar `tests/seo_analytics_210.php`/`.js` junto à suíte completa.

## Refatoração 1.0.111 — limites preservados

- `app/analytics.php` continua sendo o ponto de entrada único das métricas, carregando `app/analytics_helpers.php` com `require_once`. Não alterar os nomes das funções usadas pelos controladores, relatórios e página pública.
- `analytics_helpers.php` contém somente funções auxiliares (privacidade, classificação heurística, normalização de rotas, referenciadores e localização offline). Persistência, agregação e SQL permanecem em `analytics.php`.
- `public/assets/js/seo-settings.js` tem atualizadores separados para textos, imagem social e favicon, acionados pelo mesmo `summary()`; preservar snapshots e o cancelamento dos diálogos, validação do formulário, consentimento para desativar indexação e integração com biblioteca.
- Testes da refatoração: `php tests/refactor_211.php` e `node tests/refactor_211.js`, além de toda a regressão. Schema 37 e backup v12 inalterados.

## Refatoração 1.0.112 — Integrações

- O ponto de entrada `app/admin_views/routes/integrations.php` prepara as variáveis e inclui os blocos estáticos de `app/admin_views/routes/integrations/`. Preservar o escopo PHP dos `require` fixos, as ações POST/CSRF/senha, os IDs utilizados por `integrations-admin.js`, os diálogos e o HTML acessível.
- Os testes de contrato que leem o template como texto usam `tests/support/integrations_source.php` para recompor os fragmentos, sem executar PHP. Não restaurar dependências de um arquivo monolítico.
- O teste `tests/integrations_refactor_212.php` compara SHA-256 do template remontado com a versão 1.0.111; revisar conscientemente esse contrato antes de futuras alterações legítimas no HTML.
- Sem schema novo (37), sem backup novo (v12) e sem alterações de credenciais/API. Testar a página em sessão real no Plesk antes de considerar a implantação validada.

## Refatoração 1.0.113 — Wizard e controlador de notícias

- `app/admin_controllers/news.php` é a entrada privada única da rota POST `news`, mantida por `igua_admin_post_module()`. Ela faz cinco `require` fixos de `app/admin_controllers/news/`, na ordem `taxonomy.php`, `gemini.php`, `drafts.php`, `save.php` e `lifecycle.php`. Nunca derivar caminhos de `$_GET`, `$_POST` ou `$action`.
- Os fragmentos são executados no mesmo escopo do ponto de entrada (`$db`, `$me`, `$action`, sessão e payload), preservando `require_ability`, CSRF de `public/admin.php`, validações, respostas JSON, redirecionamentos e a semântica de `exit`.
- `save.php` mantém transação, revisões, mídias, anexos e ZIP, remoção de uploads após rollback confirmado e conservação de arquivos quando o resultado do COMMIT é incerto. Não fragmentar a transação sem testes com MariaDB e falhas injetadas.
- `tests/support/news_controller_source.php` recompõe os cinco fragmentos sem executá-los para regressões estáticas antigas; `tests/news_controller_refactor_213.php` confirma equivalência SHA-256 exata com 1.0.112 e rotas/ACL. Em futuras mudanças de negócio legítimas, atualizar conscientemente esse contrato.
- Os oito módulos JS e os oito componentes de view do editor permanecem como antes; regenerar `news-editor.js` somente se suas fontes JS forem alteradas. Schema 37 e backup v12 inalterados.

## Segurança de recuperação 1.0.114

- `backup_path` e `list_backups` devem rejeitar tanto arquivos de backup quanto a pasta de backups quando forem links simbólicos; preservar nomes válidos e downloads de arquivos regulares.
- Teste adversarial `tests/recovery_safety_214.php` é obrigatório junto à suíte existente; não conectar banco real, sobrescrever mídias nem executar atualização de código nesse teste offline.
- O formato do backup permanece v12 e o banco schema 37. A suíte em `tests/integration/restore_mariadb_134.php` requer MariaDB isolado, vazio, ext-zip real e ativação explícita; a execução da suíte PHP local não prova recuperação de produção.
- Consultar `docs/SEGURANCA_RECUPERACAO_114.md` para limites de testes e cenários ainda pendentes, em especial falha durante rollback do atualizador, falta de disco e timeout.

## Atualizador e injeção de falhas — 1.0.115

- `igua_updater_rollback_plan` precisa verificar **todos** os snapshots e o journal antes da primeira escrita no código; não remover o pré-check para acelerar rollback. Arquivos alterados depois de `complete` bloqueiam restauração automática.
- Recuperação manual deve ativar a manutenção e gravar `recovery_needed` **antes** de modificar arquivos. O estado `rolled_back` só pode ser registrado após restaurar; falha para limpar marcador não é sucesso e deve permitir tentativa posterior.
- `igua_updater_apply_code` mantém `VERSION` por último. Os callbacks opcionais de `igua_updater_apply_code` e `igua_updater_rollback` só servem à injeção de falhas em testes offline; **não os conectar à entrada HTTP, parâmetros POST ou configuração de produção**.
- Executar `tests/updater_failure_215.php` com arquivos efêmeros, além da regressão completa. Testes locais não comprovam resistência a timeout do Plesk, falta de disco real nem restauração SQL. Consulte `docs/RECUPERACAO_FALHAS_115.md`.
