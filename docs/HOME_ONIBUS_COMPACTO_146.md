# Resumo de ônibus compacto da home — v1.0.146

## Mudança

- No espaço dos antigos filtros, a home mostra um **único próximo ônibus**, vindo da mesma fonte de horários já cadastrados. A consulta pede `bus_home_summary(..., 1)`, preservando ordenação cronológica do módulo existente, timezone `America/Sao_Paulo` e horizonte máximo de 14 dias.
- Cabeçalho simples `🚌 Próximo ônibus` e link `Ver horários` para `/index.php?p=onibus`.
- Abaixo, somente hora, dia (Hoje/Amanhã), cidade de destino e contagem até a saída. O próprio horário/destino leva aos filtros da página completa.
- Não exibir no resumo as duas saídas adicionais, “TRANSPORTE REGIONAL”, “Saídas programadas”, nome da empresa, rodoviária nem o aviso final de falta de rastreamento.
- Se não houver partidas ou o banco estiver indisponível, mostrar apenas uma linha curta de estado, sem horários inventados.
- O módulo completo de ônibus continua com detalhes e eventuais alertas; não foi alterado.

## Compatibilidade e verificação

Sem migração do banco, GPS, rotas novas, modificações em uploads, configurações privadas ou serviços AWS. Preservados schema 37 e backup v12. Verificar `php tests/home_bus_search_245.php`, `php tests/home_bus_compact_246.php`, `python3 tools/build_css.py --check`, `php -l app/public_views/routes/home.php`, e integridade do ZIP. Testes reais em Chrome Android/MariaDB/EC2 ainda pendentes.
