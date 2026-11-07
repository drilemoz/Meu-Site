# Relatório de alterações e linhas | IguaNews 1.0.30

Comparação da versão 1.0.29 com o código-fonte 1.0.30.

- 8 arquivos alterados, 14 adicionados e 0 removidos (incluindo limpeza dos logs operacionais).
- Total de arquivos de origem na nova versão, excluindo os dois relatórios incorporados: 183.
- `public/admin.php` passou de 560 para 238 linhas (ações administrativas extraídas sem mudanças nas URLs).
- Preservados banco/esquema, backup lógico v10, mídia v2, contas, notícias, SEO, publicidade, rotas e o editor de 3 etapas.

## Controladores privados criados

| Arquivo | Papel |
|---|---|
| `app/admin_controllers/media.php` | Biblioteca e downloads de mídia |
| `app/admin_controllers/downloads.php` | Downloads de backups e pacotes |
| `app/admin_controllers/news.php` | Notícias, rascunhos, revisões, duplicações e publicações |
| `app/admin_controllers/editorial.php` | Editorias, fontes, autores, demonstrações |
| `app/admin_controllers/ads.php` | Publicidade e campanhas demonstrativas |
| `app/admin_controllers/bus.php` | Administração de transporte |
| `app/admin_controllers/system.php` | Configurações, SEO, métricas e saúde |
| `app/admin_controllers/users.php` | Usuários e permissões |
| `app/admin_controllers/backups.php` | Backup e restauração |
| `app/admin_controllers/post_routes.php` | Mapeamento seguro de ações POST para controladores |

## Arquivos modificados / criados

| Arquivo | Estado | Linhas antes → depois |
|---|---|---:|
| `app/admin_controllers/ads.php` | novo | – → 14 |
| `app/admin_controllers/backups.php` | novo | – → 29 |
| `app/admin_controllers/bus.php` | novo | – → 4 |
| `app/admin_controllers/downloads.php` | novo | – → 19 |
| `app/admin_controllers/editorial.php` | novo | – → 62 |
| `app/admin_controllers/media.php` | novo | – → 38 |
| `app/admin_controllers/news.php` | novo | – → 125 |
| `app/admin_controllers/post_routes.php` | novo | – → 47 |
| `app/admin_controllers/system.php` | novo | – → 35 |
| `app/admin_controllers/users.php` | novo | – → 30 |
| `docs/ARQUITETURA_ADMIN.md` | novo | – → 37 |
| `docs/VALIDACAO_130.md` | novo | – → 9 |
| `storage/logs/.gitkeep` | novo | – → – |
| `tests/admin_routes_130.php` | novo | – → 35 |
| `AGENTS.md` | alterado | 69 → 71 |
| `CHANGELOG.md` | alterado | 232 → 241 |
| `README.md` | alterado | 336 → 343 |
| `VERSION` | alterado | 1 → 1 |
| `public/admin.php` | alterado | 560 → 238 |
| `tests/article_workflow.php` | alterado | 32 → 33 |
| `tests/media_118.php` | alterado | 21 → 21 |
| `tests/seo_url_124.php` | alterado | 60 → 60 |

## Testes

- `php -l`: 76 arquivos PHP válidos.
- `node --check`: 14 arquivos JavaScript válidos.
- `php tests/*.php`: 27 suítes aprovadas, incluindo `admin_routes_130.php`.
- `node tests/*.js`: 5 suítes aprovadas.
- `python3 tests/seo_http_125.py`: 8 cenários HTTP aprovados.
- ZIP: CRC/integridade verificada.

## Limites da validação

Não foram executados fluxos integrados com sessão administrativa real, MySQL/MariaDB, navegador Android nem restauração de backup em ambiente isolado.
O código mantém as permissões e chamadas originais, mas esses fluxos precisam ser validados em uma instalação de teste.

## Inventário completo

Consulte `IguaNews-v1.0.30-Relatorio-Linhas.csv` para tamanho, linhas e SHA-256 de todos os arquivos, com comparação da versão anterior.
