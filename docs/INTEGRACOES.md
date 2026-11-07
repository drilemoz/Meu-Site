# Integrações, Analytics e verificações — versão 1.0.76

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

## Verificador de Páginas

Checagem **local**, sem HTTP externo: homepage, categorias e notícias públicas. Verifica título, descrição, URL canônica, regra de indexação, imagem vinculada, dados estruturados e sitemap. Isso não é uma medição PageSpeed nem inspeção do índice do Google.

## Provedores futuros

Gemini, Google Ads, GTM, PageSpeed Insights, Bing e outros aparecem no catálogo como planejamento onde não há adaptador operacional. Não declarar conexão, aprovação ou monitoramento sem prova externa.

Testes: `tests/integrations_169.php`, `tests/page_inspector_170.php`, `tests/search_dns_172.php`, `tests/ga4_realtime_171.php` e `tests/ga4_consent_168.js`.
