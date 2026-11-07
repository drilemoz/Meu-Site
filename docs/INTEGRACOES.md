# Integrações, Analytics e verificações — versão 1.0.89

A Central de Integrações é acessível em **Configurações → Integrações e APIs** e requer permissão de gerenciamento de configurações. O projeto preserva schema MySQL 36.

## Navegação e ajuda (v1.0.89)

- Corrigido atributo `data-integrations-nav` antes misturado indevidamente a `class`: as quatro abas Google/Diagnóstico/Páginas/Catálogo voltam a alternar conteúdo sem carregar tudo em sequência.
- Quatro serviços Google abrem com `<dialog>`: centralizado no desktop e em tela inteira no celular. Rolagem apenas dentro da janela, com cabeçalho, Fechar e Voltar fixos. Campos dos formulários **são movidos**, nunca clonados, mantendo suas validações nativas, CSRF e eventuais senhas informadas até que a navegação termine.
- Ajudas `?`, configuração de conta de serviço GA4, OAuth AdSense, metatag, listas de relatórios, sugestões PageSpeed, inspeção de indexação e histórico de diagnósticos abrem na mesma janela, com navegação interna.
- Links de consultas após POST retornam para o serviço apropriado, sem `scrollIntoView()`. O script geral do admin não expande os cartões da Central por fragmento `#`.
- Os guias da tela explicam como localizar ID `G-...`, ID numérico da propriedade GA4, `ca-pub-...`, DNS TXT/metatag, permissões de conta de serviço e OAuth, métricas Lighthouse e links oficiais. **Não divulgam chaves** e não acrescentam chamadas externas automáticas.
- Sem suporte do navegador a `<dialog>`, os cartões permanecem acessíveis com expansão convencional como fallback. A interface moderna depende de JS habilitado.
- Testes: `tests/integrations_modal_189.js` valida navegação simulada, movimentação dos formulários, ajuda, voltar, fechar e links profundos; `tests/integrations_modal_189.php` protege estrutura, botões `?`, segurança e estilos. Validação visual manual no Android e na hospedagem continua necessária.

## Google Analytics 4

- As opções `ga4_enabled` e `ga4_measurement_id` são centralizadas; a tag `G-...` só é carregada no site público quando ativada **e** autorizada pelo visitante. Respeitar GPC/DNT e a revogação de consentimento.
- A consulta autorizada da **Data API** exige também o ID numérico da propriedade, conta de serviço com acesso **Leitor** à propriedade, habilitação da API e importação do JSON **privado**; o arquivo fica em `storage/integrations/`, não no HTML, GitHub, backup, nem nos pacotes.
- **Testar recebimento** consulta dados dos últimos 30 minutos e histórico recente de 7 dias quando a hospedagem tem cURL/OpenSSL/rede e credenciais válidas. O total pertence à *propriedade GA4*, não comprova origem específica do fluxo nem garante visita atual.
- A ausência de eventos não prova que a tag falhou; o consentimento ou filtros também podem impedir a coleta.

## Google AdSense e publicidade (v1.0.87)

O ID `ca-pub-...` continua em Publicidade e **não comprova aprovação**. O acesso à API de relatórios exige autorização por **conta Google de usuário**, pois a AdSense Management API **não aceita contas de serviço**. Nunca reutilizar a chave JSON do GA4 para o AdSense.

1. Em **Geral e SEO**, configure a URL HTTPS pública exata do portal, sem porta, caminho ou parâmetros. Acesse o painel por esse domínio.
2. Ative **AdSense Management API** no Google Cloud e crie um cliente OAuth 2.0 do tipo **Aplicativo da Web**. Configure a tela de consentimento e registre a URL de retorno mostrada em **Integrações → Google AdSense** (`https://dominio/admin.php?action=integrations_adsense_callback`).
3. Cadastre **ID e segredo OAuth**, confirmando com a senha administrativa. Eles serão armazenados em `storage/integrations/adsense-oauth.json` com permissões restritas, excluídos de Git, ZIP, backup e HTML.
4. Toque em **Autorizar com Google**, escolha uma conta com acesso ao `ca-pub` cadastrado e conceda **adsense.readonly**. O backend valida `state` aleatório, PKCE, sessão/usuário, domínio HTTPS e prazo de 10 minutos. O código é consumido uma vez.
5. Toque em **Consultar relatório AdSense**. Uma consulta manual por conta usa OAuth refresh token, `accounts.list` para conferir o ca-pub e `reports:generate` com intervalo `LAST_30_DAYS` e métricas de receita estimada, impressões, cliques e visualizações de página.

O relatório representa a **conta do editor inteira**, não necessariamente só este portal; valores estimados não são pagamentos recebidos. O Google pode devolver dados vazios ou recusá-los por ausência de aprovação, restrições da conta ou de consentimento. Resultados resumidos ficam na sessão admin até 1 hora; tentativas têm intervalo de 90 segundos e 3 minutos após sucesso. Segurança: TLS verificado, hosts e destinos fixos, respostas limitadas, CSRF e ACL, senha para gravar/remover credenciais. Os tokens não são enviados em páginas, logs ou banco. **Desconectar no portal não revoga a autorização no Google**: use a área de segurança da conta para revogá-la lá.

Documentação oficial: https://developers.google.com/adsense/management/getting_started e https://developers.google.com/adsense/management/reference/rest/v2/accounts.reports/generate.

## Search Console e DNS

- **Metatag**: apenas o valor de verificação permitido é publicado como atributo HTML escapado; serve a propriedades de prefixo de URL.
- **DNS TXT**: a Central consulta o TXT público de `seo_site_url`, sem aceitar host arbitrário, e compara o registro esperado se fornecido. TXT encontrado **não** confirma o estado da propriedade no Google.
- A consulta **autorizada à Search Console API** é uma função separada de DNS/metatag. Ela verifica acesso de fato à propriedade na conta de serviço; sem permissão, exibe erro e não inventa aprovação. A partir da v1.0.85, a **inspeção individual de URLs** tem ação separada na aba Páginas e utiliza a URL Inspection API oficial.

## Search Console API real — versão 1.0.84

1. Em **Geral e SEO**, configure a URL pública HTTPS principal na raiz do site (sem porta e sem caminho).
2. Na **Google Cloud Console**, habilite a **Search Console API** para o projeto da conta de serviço já cadastrada na seção GA4. Se ainda não possui conta de serviço, crie e importe o JSON no formulário privado da seção GA4. Não inclua JSON, chave RSA ou tokens no repositório, ZIPs, logs ou conversas.
3. No **Google Search Console → Configurações → Usuários e permissões**, adicione o **e-mail da conta de serviço** como usuário da propriedade desejada. Uma propriedade verificada via DNS **não libera automaticamente** a conta de serviço. A conta de serviço é outra identidade, mesmo que pertença ao mesmo projeto Google Cloud.
4. Na Central, escolha **Domínio** (`sc-domain:iguatama.online`) ou **Prefixo HTTPS** (`https://iguatama.online/`) conforme a propriedade **exata** cadastrada e autorize a conta de serviço. Salve e toque **Consultar dados reais**.

- A API usa OAuth JWT com escopo `https://www.googleapis.com/auth/webmasters.readonly`, busca em `sites.list` a propriedade e sua permissão e executa somente `searchAnalytics.query`, sempre em endpoints Google fixos, TLS verificado e sem redirecionamento.
- Relatório em modo **Pesquisa Web**, com totais sem dimensões e até oito pesquisas/páginas principais. Período: 28 dias completos, de 30 dias atrás até 3 dias atrás, datas no fuso **America/Los_Angeles** usado pelo Search Console. O Google pode atrasar dados, suprimir pesquisas e limitar os resultados aos registros mais relevantes. Nenhuma linha é tratada como **dados indisponíveis**, não como falha do site.
- Sessão administrativa retém somente totais, rótulos resumidos, propriedade, nível de permissão e horário por até uma hora; sem persistência no banco nem exposição ao público. Impõe espera de 90 segundos entre tentativas e 3 minutos antes de repetir um relatório válido. A credencial de serviço continua privada no mesmo caminho utilizado pela GA4.
- Os relatórios agregados **não** confirmam a indexação individual; a consulta individual (v1.0.85) é outro recurso com escopo de leitura. Nenhum deles envia solicitações de indexação ou substitui o painel público do Search Console. A chave JSON fica sob `storage/integrations/` e não entra nos pacotes ou backups do aplicativo.
- O servidor precisa de PHP cURL, OpenSSL e acesso HTTPS; resposta de erro nunca exibe token ou corpo bruto do Google. Quotas e restrições de API do Google podem impedir consultas.

Documentação oficial: https://developers.google.com/webmaster-tools/v1/sites/list e https://developers.google.com/webmaster-tools/v1/searchanalytics/query.


## PageSpeed Insights (celular e computador, versões 1.0.82–1.0.83)

- A Central adiciona uma integração operacional para testar **desempenho em celular e computador**. O administrador aciona **Medir celular** ou **Medir computador**; a análise não ocorre em segundo plano, nem usa o rastreamento dos visitantes.
- Usa a URL canônica HTTPS pública de **Geral e SEO**, somente na raiz, sem credenciais embutidas, IP, porta, caminho, query ou domínio enviado via formulário. A requisição sai do PHP para o endpoint HTTPS fixo `pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed`, sem redirecionamentos, com verificação TLS e limite de resposta.
- A API Google pode ser usada sem chave para consultas ocasionais, sujeita a cotas; o servidor precisa de cURL, HTTPS e acesso externo. Se indisponível, o erro é apresentado sem inventar uma nota. O teste não instala tags ou scripts no público.
- Cada dispositivo usa uma estratégia oficial da API (`mobile` ou `desktop`) escolhida apenas por whitelist do servidor; a requisição nunca recebe URL informada pelo visitante. O relatório resume nota Lighthouse de **0 a 100** e métricas (FCP, LCP, CLS quando retornadas). Armazena na **sessão administrativa** apenas esses valores, a URL, a estratégia e horário por até 1 hora; evita testes repetidos do mesmo dispositivo por 3 minutos quando existe relatório válido e limita tentativas por dispositivo a 90 segundos. Resultados mobile válidos da versão anterior continuam visíveis até expirar.
- As estratégias celular/computador representam **simulações diferentes**, não uma comparação de usuários reais. Não interpretar notas de laboratório como dados CrUX/usuários reais, certificado de SEO, aprovação no Search Console ou ranking de pesquisa. O Google pode alterar resultados, disponibilidade e cotas da API.
- Os botões **Copiar ID/TXT** copiam somente identificadores públicos dos campos visíveis; nunca a credencial privada de serviço GA4.

## Verificador de Páginas

Checagem **local**, sem HTTP externo: homepage, categorias e notícias públicas. Verifica título, descrição, URL canônica, regra de indexação, imagem vinculada, dados estruturados e sitemap. Isso não é uma medição PageSpeed nem inspeção do índice do Google.

## Google Analytics 4 — audiência e conteúdo (v1.0.87)

1. Em **Configurações → Integrações → Google Analytics 4**, configure o `G-...` para o fluxo público, o **ID numérico da propriedade**, e cadastre o JSON privado de uma conta de serviço Google. Ative a **Google Analytics Data API** no projeto da conta de serviço e dê permissão de **Leitor** na propriedade correspondente.
2. O botão **Testar recebimento** permanece para os últimos 30 minutos e sete dias. O novo **Consultar audiência** gera outro relatório: últimos **28 dias completos até ontem**, de acordo com a zona de tempo da propriedade GA4. Não realiza tarefas em segundo plano.
3. O painel exibe usuários ativos, sessões e visualizações em totais independentes; até 10 títulos de páginas por visualizações, oito canais padrão por sessões e cinco categorias de dispositivos por usuários ativos. Não some usuários únicos entre dimensões; pessoas podem usar mais de um dispositivo. Páginas são agrupadas pelo título da página registrado na propriedade, que pode agrupar URLs diferentes com mesmo título.
4. A API usa OAuth 2.0 por JWT RSA, escopo `analytics.readonly` e endpoint único `POST https://analyticsdata.googleapis.com/v1beta/properties/ID:batchRunReports` (4 relatórios, máximo oficial de 5 por lote). Hosts fixos, TLS sem redirecionamento, limites de resposta e duração. Sem URLs externas livres ou segredos em logs.
5. Cache na **sessão administrativa** por até 1 hora, restrito ao ID da propriedade. Há 90 segundos entre tentativas e 3 minutos antes de repetir um resultado válido. Falhas mostram erros apropriados sem exibir resposta bruta do Google; resultados vazios não significam falha do portal. O relatório não é salvo no MySQL e nenhum token é colocado na sessão.
6. **Importante:** relatórios GA4 abrangem a **propriedade**, não garantidamente só a medição `G-...` do IguatamaOnline quando existem fluxos adicionais. Dependem de eventos aceitos pelo Google, consentimento dos visitantes e atrasos/limiares. Não substituem as métricas locais e não provam indexação ou aprovação de publicidade. As categorias da API podem vir em inglês, pois refletem os valores da propriedade.

Documentação oficial: https://developers.google.com/analytics/devguides/reporting/data/v1/rest/v1beta/properties/batchRunReports e https://developers.google.com/analytics/devguides/reporting/data/v1/api-schema.

## Provedores futuros

Google Ads, GTM, Bing e outros aparecem no catálogo como planejamento onde não há adaptador operacional. Gemini dispõe de integração opcional descrita abaixo. Não declarar conexão, aprovação ou monitoramento sem prova externa.

Testes: `tests/integrations_169.php`, `tests/page_inspector_170.php`, `tests/search_dns_172.php`, `tests/ga4_realtime_171.php`, `tests/ga4_audience_186.php` `tests/search_console_api_184.php`, `tests/search_console_api_184.js`, `tests/pagespeed_181.php`, `tests/pagespeed_182.php`, `tests/integrations_181.js`, `tests/integrations_182.js` e `tests/ga4_consent_168.js`, `tests/adsense_oauth_187.php`.

## PageSpeed avançado 1.0.83

- Cada consulta solicita as quatro categorias oficiais de Lighthouse (`performance`, `accessibility`, `best-practices`, `seo`) para o dispositivo escolhido. Pontuações ausentes aparecem como **indisponíveis**, nunca como zero.
- Até quatro recomendações em português são exibidas somente se uma auditoria **de fato retornou pontuação abaixo de 0,9** e existe uma orientação mapeada para aquele identificador. A lista não é um diagnóstico exaustivo e não substitui o relatório Lighthouse completo.
- São preservados URL HTTPS fixa do site, cURL com TLS, limite de resposta, CSRF e ACL, intervalos por dispositivo e cache de até 1 hora na sessão, sem gravar relatório completo no banco.
- A nota SEO do Lighthouse verifica aspectos técnicos básicos e **não significa indexação no Google**, ranking ou verificação Search Console.

## Inspeção individual de notícias — versão 1.0.85

1. Configure `seo_site_url` como HTTPS na raiz, habilite a indexação em **Geral e SEO** e selecione a propriedade correta em **Integrações → Google → Search Console**. Habilite também a **Search Console API** no Cloud e conceda acesso da propriedade ao e-mail da conta de serviço já registrada em GA4.
2. Na aba **Páginas**, selecione uma notícia real/publicada, ou clique em **Índice Google** no editor da notícia (atalho só para administradores), e pressione **Consultar indexação**.
3. O painel apresenta o último estado conhecido no **índice do Google**, não o conteúdo atualmente disponível ao vivo. `PASS` é exibido como indexada, `NEUTRAL` como excluída, `FAIL` como problema e respostas não conclusivas permanecem explicitamente desconhecidas. Exibe ainda `coverageState`, `robotsTxtState`, `indexingState`, `pageFetchState`, `lastCrawlTime`, `googleCanonical` e `userCanonical` quando disponíveis.

- O POST administrativo contém **somente `article:ID`** e CSRF; o backend refaz a busca de uma notícia publicada no banco e reconstrói a URL canônica conforme a configuração do portal. Não é permitida URL arbitrária, de rascunho, de demonstração, com IP, HTTP ou host externo. A URL pública pode ter formato `/noticia/slug` ou `/index.php?p=noticia&slug=...` conforme a configuração de URLs amigáveis.
- A conta de serviço reutiliza o JWT com escopo `webmasters.readonly` e a validação da propriedade via `sites.list`. Usa apenas `https://searchconsole.googleapis.com/v1/urlInspection/index:inspect` com `inspectionUrl`, `siteUrl` e `languageCode=pt-BR`. Transporte cURL com TLS validado, sem redirecionar, timeout e limite de corpo; resposta da API é resumida e escapada, nunca salva integralmente.
- Sessão administrativa armazena somente o resumo de uma notícia por até **1 hora**, amarrado ao artigo/URL/propriedade. Consultas repetidas aguardam 90 s entre tentativas ou 3 min após resultado válido. Não há varredura em lote nem consulta automática, preservando cotas e consumo.
- A API **não faz teste ao vivo e não solicita indexação**. Uma notícia publicada pode ainda não ter sido rastreada; números e estados podem refletir uma versão antiga. A consulta real exige cURL/OpenSSL e permissões na hospedagem, ainda não verificadas neste ambiente.

Documentação Google: https://developers.google.com/webmaster-tools/v1/urlInspection.index/inspect e https://developers.google.com/webmaster-tools/v1/urlInspection.index/UrlInspectionResult.

## Diagnóstico central — v1.0.88 (última versão desta série)

A aba **Diagnóstico** reúne GA4, AdSense, Search Console (relatório e inspeção), PageSpeed (celular/computador) e DNS. Em cada ficha aparecem os pré-requisitos **locais**, a **última operação** e seu horário, se houve uma consulta manual desde a implantação desta versão na sessão corrente. Uma configuração aparentemente completa não significa API aprovada nem dados recebidos.

Após consultas manuais, a sessão administrativa armazena apenas nome interno do serviço, estado (sucesso/erro), categoria de erro e horário. São guardadas no máximo **12 operações**; o encerramento da sessão apaga esse registro. As categorias são *heurísticas* e podem exigir revisão da mensagem na própria operação. **Nunca** são guardados relatórios brutos, URLs de OAuth, senhas, chaves ou tokens no diagnóstico. Reconfigurar uma integração invalida a marca da última verificação desse serviço, sem modificar o histórico anterior da sessão.

O acesso à aba não faz requisições externas. Para efetuar uma nova consulta, abra o serviço desejado na aba **Google** ou a inspeção da notícia na aba **Páginas**. A Central de Saúde acompanha a integridade local da hospedagem; diagnósticos não substituem verificação real de OAuth/credenciais, permissões, aprovação AdSense nem indexação no Google.


## Google Gemini — API editorial opcional (v1.0.103)

Acesse **Configurações → Integrações e APIs → Google → Google Gemini**. No Google AI Studio, obtenha uma chave (https://aistudio.google.com/api-keys), cole no pop-up, informe o modelo disponível para sua conta (por exemplo `gemini-3.8-flash`), marque Ativar, confirme a senha administrativa e salve. A chave fica em `storage/integrations/gemini-api.json` com acesso restrito fora de `public/`, e não é incluída no código ZIP. Deixe o campo de chave vazio para conservar a já cadastrada. Para apagar, use Remover chave e confirme novamente a senha.

Depois de salvar, toque **Testar API**. O teste é uma chamada real a `https://generativelanguage.googleapis.com/v1beta/models/MODELO:generateContent` usando o cabeçalho `x-goog-api-key` (não é verificação fictícia de formato). O servidor exige HTTPS, TLS válido, cURL, modelo validado, respostas limitadas e intervalos entre tentativas. Erros 401/403/404/429/503 são explicados sem divulgar o corpo da resposta nem o segredo. O modelo deve ser escolhido conforme a lista atual do Google: https://ai.google.dev/gemini-api/docs/models.

No **Novo cadastro de notícias**, apenas o ícone Gemini ✦ aparece nas etapas Título, Descrição, Matéria e SEO. Cada clique transmite à API título/resumo/corpo informados (limitados) e abre uma janela com sugestão; o conteúdo só entra no formulário após clicar em **Aplicar sugestão** e ainda precisa passar pela revisão/publicação manual. O módulo não verifica fatos, não publica sozinho, não captura fotos ou anexos e não envia texto a cada tecla. A revisão humana deve conferir precisão e consentimento para conteúdo sensível. Desativar a integração conserva o restante do editor e SEO local.

Os testes offline verificam formato, armazenamento, rotas e erros sem usar credencial verdadeira. Na hospedagem, o administrador precisa executar o Testar API com chave própria. O Google pode alterar preços, quotas e modelos.

### Ajustes v1.0.104

O serviço Gemini agora aparece com GA4/AdSense/Search Console na aba **Google**, enquanto o Catálogo contém somente integrações planejadas. **Salvar configuração** e **Testar API** ficam na mesma linha; a remoção exige senha em bloco separado. O botão de teste abre diálogo centralizado e faz POST autenticado/CSRF com resposta JSON, sem trocar de página.

Falha HTTP **503** indica normalmente capacidade temporariamente indisponível no serviço Google. Não equivale a chave inválida. O teste tem no máximo uma nova tentativa curta para erros temporários, cooldown de 30 s entre testes manuais e instruções para conferir modelos/cotas. Erros 401/403/404/429 mantêm diagnósticos distintos e sanitizados. A chave e o corpo bruto da API nunca entram no resultado.
