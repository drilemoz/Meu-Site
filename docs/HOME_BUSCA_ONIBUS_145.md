# Página inicial e busca automática — versão 1.0.145

## Mudanças de interface

- Removida **apenas da página inicial** a faixa horizontal de filtros `Todas / Geral / Cidade / Política / Eventos`. Links de editorias na navegação, rodapé e páginas de categorias permanecem.
- No espaço anteriormente ocupado pelos filtros, um card compacto `Próximos ônibus` mostra até três partidas programadas saindo da cidade configurada em `setting('city')`, com destino, empresa/ponto, dia, hora e contagem regressiva. `Ver horários` abre o módulo integral.
- O card consulta o módulo já existente (`bus_routes_from_city`, `bus_departures_for_routes`, `bus_upcoming_departures`) e **não** cria tabela, copia horários ou consulta GPS. Em ausência de dados ou erro do banco, exibe mensagem clara sem derrubar a home.
- O campo superior ganha sugestões automáticas de **notícias publicadas**, **editorias** e **trechos de ônibus ativos**, via `GET /api_search.php?q=...` depois de dois caracteres e de pausa de 300 ms. Resultados limitados a seis itens, sem HTML vindo do servidor. Setas, Enter e Esc funcionam; sem JavaScript, o formulário tradicional continua abrindo o acervo de notícias.
- A caixa de pesquisa não promete serviços ou eventos não implementados na consulta unificada. Artigos ainda são encontrados por título e resumo, como no acervo atual.

## Segurança, desempenho e limites

- Consultas com parâmetros PDO, `LIKE` escapado, até 100 caracteres de entrada e limite SQL; somente notícias publicadas e rotas ativas. Endpoint aceita apenas GET, responde JSON e não envia dados privados.
- Debounce no navegador, cancelamento de requisições anteriores e proteção contra respostas fora de ordem. **Não existe rate limit do lado servidor** neste recurso; para alto tráfego o Nginx pode precisar de limite de requisições e de monitoramento.
- Datas/horários de partida seguem `America/Sao_Paulo` e as regras de dia da semana já cadastradas, com horizonte máximo de 14 dias. Não é rastreamento ao vivo; a empresa deve confirmar viagens em feriados e mudanças.
- Sem alteração de MariaDB/schema 37, backup v12, autenticação ou deploy AWS. Atualização do código é necessária no servidor para ativar o recurso; não ocorreu implantação nesta etapa.

## Verificação

`php -l` em arquivos PHP modificados, `node --check` no JavaScript de sugestões, `python3 tools/build_css.py --check` e `php tests/home_bus_search_245.php`. Teste real com MySQL/MariaDB e em Chrome Android permanece pendente.
