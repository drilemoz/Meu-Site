# Correção da pesquisa automática — 1.0.149

## Defeito observado

Na versão 1.0.147/1.0.148, o painel da busca só mostrava URLs iniciadas com `/index.php?`. Quando a configuração `seo_pretty_urls=1` estava ativa, `url()` em `app/bootstrap.php` passava a gerar `/noticia/<slug>` e `/categoria/<slug>`. Essas notícias e editorias eram descartadas no navegador, enquanto as linhas de ônibus permaneciam visíveis.

## Ajuste

`public/assets/js/search-suggest.js` agora aceita ambos os formatos de URL internos, mantendo a proteção contra links externos, protocolos inseguros e outras páginas. A API `public/api_search.php` e a consulta SQL `app/public_search.php` não foram alteradas. O parâmetro `v` do script no layout é derivado de `VERSION`, invalidando cache após instalar 1.0.149.

## Verificações

- `python3 tests/search_suggestions_ui_247.py` testa URLs amigáveis de notícia e categoria, legada e ônibus, links proibidos, teclados e estados de erro.
- `php tests/home_bus_search_245.php` preserva a busca do backend, inclusive limite, ordem e matérias publicadas.
- Regressões do projeto e auditoria de empacotamento.

## Implantação

O ZIP de atualização altera código público, não MariaDB, o ADM, o serviço de deploy da EC2 nem o armazenamento. Instalar após backup e conferir no Chrome Android com SEO amigável ativado; verificar também comportamento com URLs legadas. Testes locais não comprovam execução na EC2.
