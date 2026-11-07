# Central de Integrações — Meu Site v1.0.69

## Rotas e acesso
- Menu: **Configurações → Integrações e APIs** (`/admin.php?action=integrations`). Requer `manage_settings`.
- Salvamentos POST separados: `integrations_save_ga4`, `integrations_save_adsense`, `integrations_save_verification`, sempre sob CSRF e login administrativo.
- GA4 permanece com seu consentimento prévio e controles GPC/DNT/opt-out. A tela de Métricas salva apenas as métricas internas; a publicidade mantém os anúncios e controles gerais sem regravar o ID de editor.

## Chaves existentes e novas
| Chave em `settings` | Utilização | Situação |
|---|---|---|
| `ga4_measurement_id` | ID de medição `G-...` | Preexistente, fonte única |
| `ga4_enabled` | Ativação consentida do GA4 | Preexistente, fonte única |
| `adsense_publisher` | ID `ca-pub-` | Preexistente, fonte única |
| `google_site_verification` | Valor da metatag de propriedade por URL prefix | Novo, opcional |

A nova chave usa tabela `settings` genérica existente: **não migra** esquema 36. Valores vazios desativam a respectiva integração ou retiram a metatag. Configurar AdSense **não ativa** anúncios automaticamente.

## Verificação e limitações
- O diagnóstico confere valores locais HTTPS configurado, `sitemap.php` presente, opção de indexação, GA4 configurado e ativo, ID de editor AdSense, `ads.txt` correspondente e metatag configurada.
- Todos os resultados são **locais e somente leitura**. Um sitemap existente pode não ser acessível publicamente; configuração de tag não prova recebimento de dados; conteúdo de `ads.txt` não comprova aceitação Google.
- A metatag aparece no `<head>` da página pública, escapada, sem permitir HTML de usuário. Para propriedades de domínio Search Console, verificação DNS segue necessária. Concluir a verificação em https://search.google.com/search-console .
- Outros serviços (Google Ads, Gemini, GTM, PageSpeed, Bing, Clarity, Cloudflare, IndexNow) ainda não se conectam às APIs, não solicitam credenciais e não adicionam scripts ao portal.

## Segurança e compatibilidade
- Sem scripts externos de rastreamento no painel, sem chaves no código ou logs, sem escrita em uploads ou storage fora das configurações do banco.
- Antigos POST de Métricas e Publicidade permanecem compatíveis, mas não apagam IDs caso os respectivos campos estejam ausentes.
- Sem mudanças de banco, backups, schema 36, formato ZIP v2 ou updater. Faça simulação e backup antes de instalar a atualização.
- Compatibilidade e experiência mobile exigem teste real em Chrome/Android e hospedagem PHP-FPM/MariaDB.

## Testes
`php tests/integrations_169.php`, `php tests/analytics_168.php`, `php tests/ads_admin_159.php`, `php tests/smoke.php`, `node tests/ga4_consent_168.js`, `python3 tools/build_css.py --check` e `php -l` nos arquivos PHP.
