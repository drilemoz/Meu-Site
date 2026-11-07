# IguaNews 1.0.31: relatório de mudanças e inventário

## Escopo

Refatoração estrutural dos arquivos-fonte CSS mantendo o visual entregue ao navegador **byte a byte**. Não houve mudança de esquema SQL, de dados, URLs, endpoints ou do formato de backup.

### Arquivos criados ou alterados

| Arquivo | Estado | Descrição |
|---|---|---|
| `public/assets/css/modules/` | 15 novos arquivos CSS | Código-fonte separado por módulos e ordenado para preservar a cascata original. |
| `tools/build_css.py` | arquivo novo | Gerador e verificador de CSS consolidado, sem dependência em produção. |
| `public/assets/css/style.css` | sem alteração de bytes | 90.852 bytes e SHA-256 idêntico à versão 1.0.30. |
| `tests/css_modules_131.php` | arquivo novo | Confere módulos, ordem, conteúdo montado, ausência de @import e carregamento nas páginas. |
| `docs/CSS_MODULOS_131.md` | arquivo novo | Manual de edição e reconstrução do CSS sem alterar HTML. |
| `docs/VALIDACAO_131.md` | arquivo novo | Documentação de testes e limitações. |
| `README.md` | atualizado | Explica fontes CSS e como reconstruir. |
| `CHANGELOG.md` | atualizado | Registra a versão e as garantias da refatoração. |
| `AGENTS.md` | atualizado | Regras de manutenção CSS e testagem. |
| `VERSION` | atualizado | Sequência 1.0.30 → 1.0.31. |

## Validações

- PHP lint: **77/77**.
- Sintaxe JavaScript: **14/14**.
- Suítes PHP: **28/28**.
- Suítes JavaScript: **5/5**.
- HTTP local: **8 cenários**.
- CSS montado: **15 módulos**, conteúdo completo idêntico ao de 1.0.30, sem `@import` nem requisições extras.
- SHA-256 do CSS: `a71d0dd4ccefffbae4b94d67c7a926898d60cd11c6678931253d2298fac9073a`.

## Limitações

A manutenção do CSS foi validada por equivalência de bytes e testes estáticos; não substitui inspeção visual real no Android. Não foi usada base MariaDB real neste ambiente.

## Inventário de arquivos

- Arquivos considerados: **204**, excluindo estes dois relatórios autodescritivos.
- Relatório CSV com linhas, tamanho em bytes e SHA-256: `docs/RELATORIO_ARQUIVOS_LINHAS_131.csv`.
- O CSS fonte está dentro do ZIP em `public/assets/css/modules/`; `style.css` permanece pronto para uso.

### Arquivos desta versão (linhas)

| Arquivo | Linhas | Bytes |
|---|---:|---:|
| `.gitignore` | 14 | 242 |
| `AGENTS.md` | 73 | 14025 |
| `CHANGELOG.md` | 250 | 27620 |
| `README.md` | 347 | 37973 |
| `VERSION` | 1 | 7 |
| `app/admin_controllers/ads.php` | 14 | 1064 |
| `app/admin_controllers/backups.php` | 29 | 2304 |
| `app/admin_controllers/bus.php` | 4 | 391 |
| `app/admin_controllers/downloads.php` | 19 | 1026 |
| `app/admin_controllers/editorial.php` | 62 | 4765 |
| `app/admin_controllers/media.php` | 38 | 3439 |
| `app/admin_controllers/news.php` | 125 | 10986 |
| `app/admin_controllers/post_routes.php` | 47 | 1684 |
| `app/admin_controllers/system.php` | 35 | 2874 |
| `app/admin_controllers/users.php` | 30 | 2970 |
| `app/ads.php` | 107 | 6882 |
| `app/ads_admin.php` | 107 | 14423 |
| `app/ads_examples.php` | 39 | 4041 |
| `app/analytics.php` | 182 | 10383 |
| `app/analytics_admin.php` | 47 | 4894 |
| `app/article_workflow.php` | 115 | 7202 |
| `app/backup.php` | 304 | 21055 |
| `app/bootstrap.php` | 150 | 8614 |
| `app/bus.php` | 153 | 9476 |
| `app/bus_admin.php` | 123 | 14982 |
| `app/bus_import.php` | 185 | 13807 |
| `app/bus_public.php` | 45 | 7785 |
| `app/bus_wizard.php` | 145 | 15830 |
| `app/cities.php` | 52 | 3831 |
| `app/contact.php` | 71 | 7230 |
| `app/editor_examples.php` | 64 | 5442 |
| `app/editorial.php` | 34 | 2061 |
| `app/health.php` | 106 | 7615 |
| `app/installer.php` | 85 | 5466 |
| `app/media_admin.php` | 19 | 3214 |
| `app/media_library.php` | 143 | 11240 |
| `app/news_admin_view.php` | 108 | 19334 |
| `app/news_archive.php` | 109 | 5710 |
| `app/news_editor.php` | 360 | 26013 |
| `app/requirements.php` | 95 | 7270 |
| `app/sample_news.json` | 1302 | 155113 |
| `app/schema.php` | 220 | 13783 |
| `app/seo_admin.php` | 41 | 4232 |
| `app/seo_audit.php` | 24 | 1993 |
| `bin/analytics_prune.php` | 10 | 449 |
| `bin/backup.php` | 19 | 886 |
| `composer.json` | 6 | 172 |
| `docs/ARQUITETURA_ADMIN.md` | 37 | 2527 |
| `docs/BACKUP.md` | 98 | 8173 |
| `docs/CSS_MODULOS_131.md` | 49 | 3392 |
| `docs/DEPLOY.md` | 37 | 2868 |
| `docs/EDITOR_2_0.md` | 27 | 2422 |
| `docs/EDITOR_INTELIGENTE.md` | 52 | 5289 |
| `docs/GUIA_PREENCHIMENTO_NOTICIAS.md` | 60 | 4711 |
| `docs/METRICAS.md` | 46 | 5275 |
| `docs/MIDIAS_118.md` | 42 | 4623 |
| `docs/MIGRACAO.md` | 45 | 5327 |
| `docs/NOTICIAS_E_FONTES.md` | 17 | 2090 |
| `docs/ONIBUS.md` | 27 | 2821 |
| `docs/PUBLICIDADE.md` | 54 | 4643 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_118.csv` | 115 | 4340 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_118.md` | 202 | 8784 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_119.csv` | 122 | 3827 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_119.md` | 140 | 5912 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_120.csv` | 126 | 4771 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_120.md` | 34 | 1847 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_121.csv` | 129 | 4916 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_121.md` | 38 | 2412 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_122.csv` | 134 | 5139 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_122.md` | 36 | 2667 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_123.csv` | 139 | 5345 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_123.md` | 176 | 9243 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_124.csv` | 146 | 5489 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_124.md` | 49 | 3742 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_126.csv` | 159 | 6163 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_126.md` | 60 | 3737 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_128.csv` | 163 | 5324 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_128.md` | 26 | 1227 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_129.csv` | 168 | 16388 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_129.md` | 43 | 1940 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_130.csv` | 184 | 20637 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_130.md` | 68 | 3453 |
| `docs/SAUDE_SISTEMA.md` | 48 | 3889 |
| `docs/SEO_CENTRAL_125.md` | 31 | 2739 |
| `docs/SEO_URLS_124.md` | 45 | 3736 |
| `docs/USUARIOS_E_SEO.md` | 33 | 2660 |
| `docs/VALIDACAO_106.md` | 12 | 1346 |
| `docs/VALIDACAO_107.md` | 10 | 911 |
| `docs/VALIDACAO_108.md` | 14 | 975 |
| `docs/VALIDACAO_109.md` | 9 | 876 |
| `docs/VALIDACAO_110.md` | 14 | 1640 |
| `docs/VALIDACAO_111.md` | 10 | 791 |
| `docs/VALIDACAO_112.md` | 23 | 1224 |
| `docs/VALIDACAO_113.md` | 20 | 1155 |
| `docs/VALIDACAO_114.md` | 19 | 916 |
| `docs/VALIDACAO_115.md` | 16 | 1416 |
| `docs/VALIDACAO_116.md` | 23 | 1582 |
| `docs/VALIDACAO_117.md` | 32 | 2170 |
| `docs/VALIDACAO_119.md` | 21 | 1802 |
| `docs/VALIDACAO_120.md` | 17 | 1383 |
| `docs/VALIDACAO_121.md` | 22 | 2057 |
| `docs/VALIDACAO_122.md` | 27 | 2082 |
| `docs/VALIDACAO_123.md` | 35 | 2180 |
| `docs/VALIDACAO_124.md` | 13 | 1810 |
| `docs/VALIDACAO_125.md` | 15 | 1353 |
| `docs/VALIDACAO_126.md` | 28 | 2310 |
| `docs/VALIDACAO_128.md` | 31 | 1621 |
| `docs/VALIDACAO_129.md` | 22 | 1504 |
| `docs/VALIDACAO_130.md` | 9 | 867 |
| `docs/VALIDACAO_131.md` | 12 | 1084 |
| `public/.htaccess` | 11 | 343 |
| `public/ad_click.php` | 19 | 1165 |
| `public/admin.php` | 238 | 45198 |
| `public/ads.txt` | 1 | 77 |
| `public/analytics_event.php` | 33 | 2045 |
| `public/api_cities.php` | 12 | 674 |
| `public/assets/css/modules/01-foundation.css` | 10 | 20404 |
| `public/assets/css/modules/02-installer.css` | 56 | 5241 |
| `public/assets/css/modules/03-editorial-articles.css` | 19 | 3540 |
| `public/assets/css/modules/04-transport.css` | 27 | 16609 |
| `public/assets/css/modules/05-site-layout-contact.css` | 78 | 5868 |
| `public/assets/css/modules/06-advertising.css` | 18 | 3980 |
| `public/assets/css/modules/07-editor-structured.css` | 78 | 7254 |
| `public/assets/css/modules/08-analytics.css` | 22 | 3007 |
| `public/assets/css/modules/09-editorial-admin.css` | 25 | 2824 |
| `public/assets/css/modules/10-health.css` | 3 | 1408 |
| `public/assets/css/modules/11-editor-visual.css` | 21 | 2150 |
| `public/assets/css/modules/12-media-library.css` | 8 | 4168 |
| `public/assets/css/modules/13-editor-mobile.css` | 88 | 9039 |
| `public/assets/css/modules/14-seo.css` | 35 | 3133 |
| `public/assets/css/modules/15-archive.css` | 15 | 2227 |
| `public/assets/css/style.css` | 503 | 90852 |
| `public/assets/docs/GUIA_EDITOR_IGUANEWS.pdf` |  | 50206 |
| `public/assets/img/camara.jpg` |  | 57675 |
| `public/assets/img/cidade.jpg` |  | 126623 |
| `public/assets/img/cidade.webp` |  | 91066 |
| `public/assets/img/cultura.jpg` |  | 84650 |
| `public/assets/img/destaque.jpg` |  | 126623 |
| `public/assets/img/empregos.jpg` |  | 65020 |
| `public/assets/img/empregos.webp` |  | 32032 |
| `public/assets/img/esportes.webp` |  | 58600 |
| `public/assets/img/eventos.jpg` |  | 84650 |
| `public/assets/img/eventos.webp` |  | 50762 |
| `public/assets/img/futebol.jpg` |  | 94548 |
| `public/assets/img/geral.webp` |  | 26288 |
| `public/assets/img/pavimentacao.jpg` |  | 126623 |
| `public/assets/img/politica.jpg` |  | 57675 |
| `public/assets/img/politica.webp` |  | 26170 |
| `public/assets/js/analytics.js` | 31 | 1956 |
| `public/assets/js/bus-wizard.js` | 131 | 8652 |
| `public/assets/js/file-inputs.js` | 15 | 1415 |
| `public/assets/js/main.js` | 122 | 5786 |
| `public/assets/js/media-library.js` | 139 | 13494 |
| `public/assets/js/media-state.js` | 63 | 2069 |
| `public/assets/js/news-editor.js` | 272 | 22435 |
| `public/assets/js/news-upload.js` | 74 | 4867 |
| `public/assets/js/seo-audit.js` | 59 | 3885 |
| `public/download.php` | 14 | 1049 |
| `public/index.php` | 208 | 32787 |
| `public/robots.txt` | 5 | 125 |
| `public/router.php` | 20 | 1091 |
| `public/setup.php` | 198 | 18236 |
| `public/sitemap.php` | 17 | 1748 |
| `public/uploads/.gitkeep` | 0 | 0 |
| `public/uploads/.htaccess` | 4 | 112 |
| `public/uploads/thumbs/.gitkeep` | 0 | 0 |
| `public/uploads/thumbs/.htaccess` | 4 | 106 |
| `storage/geoip/.gitkeep` | 0 | 0 |
| `storage/logs/.gitkeep` | 0 | 0 |
| `tests/admin_routes_130.php` | 35 | 3105 |
| `tests/ads.php` | 41 | 4356 |
| `tests/analytics.php` | 32 | 3351 |
| `tests/archive_126.php` | 62 | 5603 |
| `tests/article_workflow.php` | 33 | 3173 |
| `tests/backup_121.php` | 89 | 4934 |
| `tests/bus.php` | 38 | 3523 |
| `tests/bus_import.php` | 23 | 2444 |
| `tests/bus_wizard.php` | 31 | 2609 |
| `tests/content.php` | 21 | 1580 |
| `tests/css_modules_131.php` | 28 | 1846 |
| `tests/editor_115.php` | 17 | 1548 |
| `tests/editor_119.php` | 24 | 2340 |
| `tests/editor_120.php` | 22 | 2380 |
| `tests/editor_122.php` | 15 | 1634 |
| `tests/editor_123.php` | 13 | 1350 |
| `tests/editor_draft_123.js` | 71 | 4886 |
| `tests/editor_examples.php` | 16 | 1139 |
| `tests/editor_media_122.js` | 150 | 8252 |
| `tests/health.php` | 31 | 2225 |
| `tests/installer.php` | 62 | 4633 |
| `tests/media_118.php` | 21 | 2021 |
| `tests/media_state_129.js` | 65 | 4351 |
| `tests/media_ui_128.php` | 11 | 1007 |
| `tests/news_editor.php` | 44 | 3950 |
| `tests/portal_108.php` | 21 | 1883 |
| `tests/seo_125.js` | 20 | 1690 |
| `tests/seo_125.php` | 45 | 3498 |
| `tests/seo_http_125.py` | 33 | 1546 |
| `tests/seo_url_124.js` | 21 | 1305 |
| `tests/seo_url_124.php` | 60 | 4980 |
| `tests/setup_diagnostics.php` | 26 | 1582 |
| `tests/smoke.php` | 49 | 5285 |
| `tests/upload_117.php` | 19 | 1679 |
| `tools/build_css.py` | 69 | 2205 |
