# Diretrizes de manutenção — Meu Site (v1.0.91)

Este repositório contém **somente o portal PHP/MySQL Meu Site**. Antes de editar código, ler `README.md`, `docs/INDICE.md`, o módulo afetado e os testes.

## Regras de projeto e versão

1. PHP 8.1+, MySQL/MariaDB, projeto web. Nenhum Kotlin, Android, Gradle, Uai Cortes, APK, design/ de outros projetos ou arquivos privados no ZIP.
2. Cada mudança entregue incrementa `VERSION` sequencialmente e sincroniza `README.md`, `CHANGELOG.md`, `RELEASE_NOTES.md`, `github-manager.json` e `app_identity.json`.
3. Gerar os arquivos com `tools/build_source_package.py` e `tools/build_update_package.py`. O ZIP de hospedagem deve conter somente `app/`, `public/` e `VERSION`, além de `release.json`/assinatura opcional; jamais `storage/`, testes, `.git`, arquivos de configuração privados ou uploads.
4. A versão de schema atual é 36. O atualizador normal **não aceita migração de schema**: planejar uma instalação assistida antes de mudar tabelas, migrações ou formato de backup. Preservar restauração de backups antigos.
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
- CSS é fonte modular (16 módulos); alterar `public/assets/css/modules/` e executar `tools/build_css.py`. Editor JS usa 8 módulos; alterar fonte e executar `tools/build_news_editor.py`.
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

## Google Drive

Ao enviar nova versão de **Meu Site** para `Projetos/Meu Site`, confirmar os **dois** ZIPs novos antes de excluir os anteriores, e verificar que a pasta contém somente a versão atual. Nunca apagar arquivos de outros projetos.
