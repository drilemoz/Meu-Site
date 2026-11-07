# Validação do IguaNews 1.0.26

## Escopo e origem

- Base: `IguaNews-v1.0.25.zip`, correspondente em tamanho ao ZIP 1.0.25 consultado na pasta do Google Drive.
- Arquivos centrais: `app/news_archive.php` (novo), `public/index.php` (consulta, SEO e interface), `public/assets/css/style.css`, `tests/archive_126.php` (novo).
- Mudança: paginação de 12 itens em Notícias e editorias, busca literal de título/resumo, filtros de editoria/período 7, 30, 90 dias, links acessíveis e canonicals apropriadas, e flag `QSA` no Apache para não perder os parâmetros dos filtros.
- Não alterados: dados, tabelas, migrações SQL, formato lógico de backup v10, pacote de mídias v2, uploads, autenticação, editor de três etapas e paginação administrativa existente.

## Verificações

- `php -l` sobre todos os arquivos PHP.
- `node --check` sobre os JS do projeto.
- `php tests/archive_126.php`: testes isolados de saneamento da entrada, vínculos secundários, parâmetro PDO/`LIMIT/OFFSET`, contagem/páginas, busca com `%` e `_`, links de filtros, acessibilidade e URL de categoria legada.
- Todas as demais suítes `tests/*.php` e `tests/*.js`.
- `python tests/seo_http_125.py`: rotas públicas com PHP CLI sem MySQL; não prova paginação com artigos reais.
- Verificações de ZIP com CRC e comparação dos caminhos com base anterior.

## Pendências

- **Integração MariaDB/MySQL real:** confirmar consultas com 0, 1, 12, 13 e 100+ matérias publicadas, inclusive categorias secundárias, datas futuras e combinação de filtros. Não foi executada nesta entrega.
- **Android/Termux:** layout dos filtros em 320–400 px, rolagem vertical, paginação e comportamento com teclado aberto; não executado fisicamente.
- Roteamento Apache e links SEO com domínio HTTPS público; redirecionamentos 301 reais de artigos publicados, não validados nesta rodada.
- Medição de desempenho/EXPLAIN com base de volume real; caso o acervo cresça muito, estudar busca FULLTEXT ou cursor paginado em vez de `LIKE '%...%'`.

## Atualização

Antes de trocar código, gerar backup completo e preservar `storage/config.php`, `storage/backups/`, `storage/media/`, `storage/analytics.key` e `public/uploads/`. Esta versão não exige migração SQL. Mantenha os arquivos de dados da instalação existente.
