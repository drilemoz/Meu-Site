# Integrações, Analytics e verificações — versão 1.0.81

A Central de Integrações é acessível em **Configurações → Integrações e APIs** e requer permissão de gerenciamento de configurações. O projeto preserva schema MySQL 36.

## Google Analytics 4

- As opções `ga4_enabled` e `ga4_measurement_id` são centralizadas; a tag `G-...` só é carregada no site público quando ativada **e** autorizada pelo visitante. Respeitar GPC/DNT e a revogação de consentimento.
- A consulta autorizada da **Data API** exige também o ID numérico da propriedade, conta de serviço com acesso **Leitor** à propriedade, habilitação da API e importação do JSON **privado**; o arquivo fica em `storage/integrations/`, não no HTML, GitHub, backup, nem nos pacotes.
- **Testar recebimento** consulta dados dos últimos 30 minutos e histórico recente de 7 dias quando a hospedagem tem cURL/OpenSSL/rede e credenciais válidas. O total pertence à *propriedade GA4*, não comprova origem específica do fluxo nem garante visita atual.
- A ausência de eventos não prova que a tag falhou; o consentimento ou filtros também podem impedir a coleta.

## Google AdSense e publicidade

O ID `ca-pub-...` é editado na Central e reutilizado pela área Publicidade. A presença de `ads.txt` e IDs não comprova aprovação da conta nem garante exibição de anúncios. Não executar scripts arbitrários inseridos por usuários.

## Search Console e DNS

- **Metatag**: apenas o valor de verificação permitido é publicado como atributo HTML escapado; serve a propriedades de prefixo de URL.
- **DNS TXT**: a Central consulta o TXT público de `seo_site_url`, sem aceitar host arbitrário, e compara o registro esperado se fornecido. TXT encontrado **não** confirma o estado da propriedade no Google.
- A confirmação de propriedade, indexação e estatísticas requer o próprio Search Console ou sua API com OAuth e permissões.

## PageSpeed Insights (novo na 1.0.81)

- A Central adiciona uma integração operacional para testar **desempenho mobile**. O administrador aciona **Medir desempenho**; a análise não ocorre em segundo plano, nem usa o rastreamento dos visitantes.
- Usa a URL canônica HTTPS pública de **Geral e SEO**, somente na raiz, sem credenciais embutidas, IP, porta, caminho, query ou domínio enviado via formulário. A requisição sai do PHP para o endpoint HTTPS fixo `pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed`, sem redirecionamentos, com verificação TLS e limite de resposta.
- A API Google pode ser usada sem chave para consultas ocasionais, sujeita a cotas; o servidor precisa de cURL, HTTPS e acesso externo. Se indisponível, o erro é apresentado sem inventar uma nota. O teste não instala tags ou scripts no público.
- O relatório resume nota Lighthouse de **0 a 100** e métricas (FCP, LCP, CLS quando retornadas). Armazena na **sessão administrativa** apenas esses valores, a URL e horário por até 1 hora; evita testes repetidos por 3 minutos quando existe relatório válido e limita tentativas a 90 segundos.
- Não interpretar nota de laboratório como dado CrUX/usuários reais, certificado de SEO, aprovação no Search Console ou ranking de pesquisa. O Google pode alterar resultados, disponibilidade e cotas da API.
- Os botões **Copiar ID/TXT** copiam somente identificadores públicos dos campos visíveis; nunca a credencial privada de serviço GA4.

## Verificador de Páginas

Checagem **local**, sem HTTP externo: homepage, categorias e notícias públicas. Verifica título, descrição, URL canônica, regra de indexação, imagem vinculada, dados estruturados e sitemap. Isso não é uma medição PageSpeed nem inspeção do índice do Google.

## Provedores futuros

Gemini, Google Ads, GTM, Bing e outros aparecem no catálogo como planejamento onde não há adaptador operacional. Não declarar conexão, aprovação ou monitoramento sem prova externa.

Testes: `tests/integrations_169.php`, `tests/page_inspector_170.php`, `tests/search_dns_172.php`, `tests/ga4_realtime_171.php` `tests/pagespeed_181.php`, `tests/integrations_181.js` e `tests/ga4_consent_168.js`.
