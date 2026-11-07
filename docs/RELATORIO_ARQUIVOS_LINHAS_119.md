# IguaNews 1.0.19 | Inventário e principais mudanças

Arquivos inventariados: 121. Textuais: 101. Total de linhas textuais: 8,371.

## Arquivos alterados e localização

- `app/news_admin_view.php`: L22 (news-mode), L47 (news-visual-editor), L60 (news-media-actions), L73 (news-direct-upload), L68 (news-cover-preview)
- `public/assets/js/news-editor.js`: L38 (function changeTab), L54 (function markdownFromVisual), L71 (function loadVisual), L137 (function snapshot)
- `public/assets/js/media-library.js`: L31 (document.querySelectorAll('[data-media-pick]')), L29 ($('.media-close'))
- `public/assets/js/file-inputs.js`: L3 (const enhance=), L10 (button.addEventListener)
- `public/assets/css/style.css`: L365 (/* IguaNews 1.0.19), L102 (@media(max-width:700px))
- `public/admin.php`: L475 (news-articles-table), L532 (/assets/js/file-inputs.js)
- `public/setup.php`: L198 (/assets/js/file-inputs.js)
- `tests/editor_119.php`: L4 (check119()

## Inventário completo

| Arquivo | Linhas | Bytes |
|---|---:|---:|
| `.gitignore` | 14 | 242 |
| `AGENTS.md` | 51 | 7673 |
| `CHANGELOG.md` | 165 | 17270 |
| `README.md` | 275 | 27692 |
| `VERSION` | 1 | 7 |
| `app/ads.php` | 107 | 6882 |
| `app/ads_admin.php` | 104 | 13243 |
| `app/analytics.php` | 182 | 10383 |
| `app/analytics_admin.php` | 47 | 4894 |
| `app/article_workflow.php` | 50 | 3097 |
| `app/backup.php` | 206 | 15283 |
| `app/bootstrap.php` | 143 | 8281 |
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
| `app/media_admin.php` | 19 | 2734 |
| `app/media_library.php` | 143 | 11240 |
| `app/news_admin_view.php` | 102 | 17470 |
| `app/news_editor.php` | 360 | 26013 |
| `app/requirements.php` | 95 | 7270 |
| `app/sample_news.json` | 1302 | 155113 |
| `app/schema.php` | 218 | 13695 |
| `bin/analytics_prune.php` | 10 | 449 |
| `bin/backup.php` | 19 | 886 |
| `composer.json` | 6 | 172 |
| `docs/BACKUP.md` | 76 | 4943 |
| `docs/DEPLOY.md` | 37 | 2868 |
| `docs/EDITOR_2_0.md` | 21 | 2061 |
| `docs/EDITOR_INTELIGENTE.md` | 52 | 5289 |
| `docs/GUIA_PREENCHIMENTO_NOTICIAS.md` | 60 | 4711 |
| `docs/METRICAS.md` | 46 | 5275 |
| `docs/MIDIAS_118.md` | 42 | 4623 |
| `docs/MIGRACAO.md` | 41 | 4700 |
| `docs/NOTICIAS_E_FONTES.md` | 17 | 2090 |
| `docs/ONIBUS.md` | 27 | 2821 |
| `docs/PUBLICIDADE.md` | 46 | 3944 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_118.csv` | 115 | 4340 |
| `docs/RELATORIO_ARQUIVOS_LINHAS_118.md` | 202 | 8784 |
| `docs/SAUDE_SISTEMA.md` | 48 | 3889 |
| `docs/USUARIOS_E_SEO.md` | 29 | 2108 |
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
| `public/.htaccess` | 5 | 128 |
| `public/ad_click.php` | 19 | 1165 |
| `public/admin.php` | 532 | 69413 |
| `public/ads.txt` | 1 | 77 |
| `public/analytics_event.php` | 33 | 2045 |
| `public/api_cities.php` | 12 | 674 |
| `public/assets/css/style.css` | 434 | 83302 |
| `public/assets/docs/GUIA_EDITOR_IGUANEWS.pdf` | 0 | 50206 |
| `public/assets/img/camara.jpg` | 0 | 57675 |
| `public/assets/img/cidade.jpg` | 0 | 126623 |
| `public/assets/img/cidade.webp` | 0 | 91066 |
| `public/assets/img/cultura.jpg` | 0 | 84650 |
| `public/assets/img/destaque.jpg` | 0 | 126623 |
| `public/assets/img/empregos.jpg` | 0 | 65020 |
| `public/assets/img/empregos.webp` | 0 | 32032 |
| `public/assets/img/esportes.webp` | 0 | 58600 |
| `public/assets/img/eventos.jpg` | 0 | 84650 |
| `public/assets/img/eventos.webp` | 0 | 50762 |
| `public/assets/img/futebol.jpg` | 0 | 94548 |
| `public/assets/img/geral.webp` | 0 | 26288 |
| `public/assets/img/pavimentacao.jpg` | 0 | 126623 |
| `public/assets/img/politica.jpg` | 0 | 57675 |
| `public/assets/img/politica.webp` | 0 | 26170 |
| `public/assets/js/analytics.js` | 31 | 1956 |
| `public/assets/js/bus-wizard.js` | 131 | 8652 |
| `public/assets/js/file-inputs.js` | 15 | 1415 |
| `public/assets/js/main.js` | 122 | 5786 |
| `public/assets/js/media-library.js` | 101 | 10695 |
| `public/assets/js/news-editor.js` | 162 | 17562 |
| `public/assets/js/news-upload.js` | 61 | 4060 |
| `public/download.php` | 14 | 1049 |
| `public/index.php` | 164 | 28434 |
| `public/robots.txt` | 5 | 125 |
| `public/setup.php` | 198 | 18236 |
| `public/sitemap.php` | 17 | 1748 |
| `public/uploads/.gitkeep` | 0 | 0 |
| `public/uploads/.htaccess` | 4 | 112 |
| `public/uploads/thumbs/.gitkeep` | 0 | 0 |
| `public/uploads/thumbs/.htaccess` | 4 | 106 |
| `storage/geoip/.gitkeep` | 0 | 0 |
| `storage/logs/.lock` | 0 | 0 |
| `storage/logs/health.log` | 2 | 150 |
| `tests/ads.php` | 34 | 3837 |
| `tests/analytics.php` | 32 | 3351 |
| `tests/article_workflow.php` | 31 | 2865 |
| `tests/bus.php` | 38 | 3523 |
| `tests/bus_import.php` | 23 | 2444 |
| `tests/bus_wizard.php` | 31 | 2609 |
| `tests/content.php` | 21 | 1580 |
| `tests/editor_115.php` | 17 | 1548 |
| `tests/editor_119.php` | 24 | 2334 |
| `tests/editor_examples.php` | 16 | 1139 |
| `tests/health.php` | 31 | 2223 |
| `tests/installer.php` | 62 | 4633 |
| `tests/media_118.php` | 21 | 1979 |
| `tests/news_editor.php` | 44 | 3950 |
| `tests/portal_108.php` | 21 | 1883 |
| `tests/setup_diagnostics.php` | 26 | 1582 |
| `tests/smoke.php` | 49 | 5285 |
| `tests/upload_117.php` | 19 | 1679 |
