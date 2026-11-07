# IguaNews 1.0.26 | Relatório de arquivos e alterações

## Base e resumo
- Base de código: ZIP **1.0.25** consultado no Google Drive; arquivo local igual em tamanho (1.493.003 bytes).
- Versão entregue: **1.0.26**.
- Sem migração SQL; backup lógico **v10** e pacote de mídias **v2** mantidos.
- Notas de instalação: preservar `storage/config.php`, `storage/backups/`, `storage/media/`, `storage/analytics.key`, `public/uploads/`.
- Inventário abaixo exclui os dois relatórios desta versão incorporados no ZIP, para evitar referência circular.
- **158** arquivos inventariados antes dos dois relatórios; **142** textuais, **11290** linhas textuais; total final do ZIP: **160** arquivos.
- Arquivos criados antes dos relatórios: **3**; modificados: **7**.

## Alterações funcionais
- Listagens públicas de notícias e editorias paginadas com 12 itens e contagem de resultados. Busca por título/resumo com `%`, `_` e `!` literais, filtros de editoria e período; vínculos com editoria secundária também contam.
- Parâmetros de busca limitados e SQL preparado; `LIMIT` e `OFFSET` vinculados como `PDO::PARAM_INT`, ordenação por data e ID.
- Links de páginas mantêm filtro; redirecionamento 301 da categoria querystring para URL amigável preserva período/página, e Apache usa `[QSA]`.
- Layout compacto responsivo sem barra horizontal, navegação acessível; categoria paginada recebe URL canônica própria. Pesquisa e filtros temporais são `noindex` quando o SEO estiver ativo.
- Sem mudança no cadastro, armazenamento, galerias, anúncios, backups, instalação ou editor de três etapas.

## Arquivos criados
- `app/news_archive.php`
- `docs/VALIDACAO_126.md`
- `tests/archive_126.php`

## Arquivos modificados
- `AGENTS.md`
- `CHANGELOG.md`
- `README.md`
- `VERSION`
- `public/.htaccess`
- `public/assets/css/style.css`
- `public/index.php`

## Referências de linhas
| Arquivo | Linhas | Locais relevantes |
|---|---:|---|
| `app/news_archive.php` | 109 | L4 function archive_search_term, L30 function archive_conditions, L56 function archive_listing, L74 function archive_link, L97 function archive_navigation |
| `public/index.php` | 208 | L5 require __DIR__.'/../app/news_archive, L86 $archive=archive_listing, L105 $indexable=setting, L108 $relative = $article, L172 class="archive-filters", L179 archive_navigation( |
| `public/.htaccess` | 11 | L10 RewriteRule |
| `public/assets/css/style.css` | 501 | L487 IguaNews 1.0.26 |
| `tests/archive_126.php` | 62 | L26 class ArchiveTestDatabase, L36 archive_escape_like, L50 archive_pages_window, L60 [L,QSA] |
| `README.md` | 323 | L5 ## 1.0.26 |
| `CHANGELOG.md` | 222 | L1 ## 1.0.26 |
| `AGENTS.md` | 65 | L65 36. A partir da 1.0.26 |
| `VERSION` | 1 | L1 1.0.26 |
| `docs/VALIDACAO_126.md` | 28 | L1 # Validação do IguaNews 1.0.26 |

## Validações executadas
- `php -l`: **63/63** arquivos PHP sem erro sintático.
- `node --check`: **12/12** arquivos JavaScript sem erro sintático.
- `php tests/*.php`: **25/25** suítes aprovadas, incluindo `archive_126.php`.
- `node tests/*.js`: **4/4** suítes aprovadas.
- `python tests/seo_http_125.py`: **8/8** cenários HTTP locais, sem MariaDB.
- CRC e caminho de todos os arquivos ZIP verificados.

## Pendências reais
- **MariaDB/MySQL**: consultas SQL executadas sobre notícias reais, paginação de 0/1/12/13/100+ notícias, duplicatas nas categorias secundárias e explicação de execução/índices.
- **Android/Termux**: layout em viewport móvel real, navegação com teclado, filtragem e teste de tempo de resposta.
- **Apache público**: conferir mod_rewrite, querystring filtrada e SEO indexável com domínio HTTPS.

Inventário CSV separado: `IguaNews-v1.0.26-Relatorio-Linhas.csv`.
