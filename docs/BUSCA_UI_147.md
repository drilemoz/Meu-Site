# Busca do cabeçalho — v1.0.147

O endpoint de sugestões `GET /api_search.php?q=...` não muda: retorna no máximo seis sugestões de notícias **publicadas**, categorias e rotas de ônibus ativas. O envio padrão da busca continua sendo `GET /index.php?p=buscar&q=...` e consulta somente o acervo de notícias. A ação de rodapé foi nomeada **Ver mais notícias** para não sugerir que a busca tradicional inclua ônibus.

## Visual

- Linhas compactas com ícone SVG local para Notícia/Editoria/Ônibus, título até duas linhas, rótulo de tipo menor e seta.
- Cabeçalho com termo e contador; destaque da correspondência com `<mark>` criado por DOM, sem `innerHTML` ou confiar em HTML da API.
- Altura máxima de 380px (limitada à área abaixo do campo até o teclado) e rolagem exclusiva da lista de resultados. Usa `window.visualViewport` quando disponível e `window.innerHeight` como fallback.
- Estados de carregamento, nenhum resultado e erro; busca padrão por Enter permanece. Abortar requisições antigas, debounce de 300ms, limite de resultados e lista acessível com navegação por setas.

## Verificações

Executar `php tests/home_bus_search_245.php`, `php tests/home_bus_compact_246.php`, `python3 tests/search_suggestions_ui_247.py`, `node --check public/assets/js/search-suggest.js`, `python3 tools/build_css.py --check` e testes de regressão. O teste de navegador local usa Chromium somente quando instalado; não substitui teste manual no Chrome Android com teclado aberto.

Sem SQL ou atualização de serviços EC2. O ZIP atualizador só leva código de `app/`, `public/` e `VERSION`, mais manifesto.
