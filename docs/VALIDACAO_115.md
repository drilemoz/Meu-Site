# IguaNews 1.0.15 · Validação local

## Verificações executadas

- Análise de sintaxe de todos os arquivos PHP e do JavaScript do editor com `node --check`.
- Suíte isolada `tests/*.php`, incluindo módulos anteriores (anúncios, métricas, notícias, ônibus, instalador, saúde e backups).
- Testes novos do Editor 2.0: formatação segura de negrito, itálico, títulos e listas; escape de HTML; rejeição de caminhos arbitrários; verificação real de tipo MIME de PNG local e recusa quando o arquivo deixa de existir.
- Integridade ZIP e contagem de arquivos.

## Limitações

O ambiente de testes contém PHP CLI sem `pdo_mysql`, portanto não foi possível executar leitura/escrita real no MariaDB, a gravação de associação de fotos reutilizadas ou as migrações. A interface também não foi validada em navegador Android físico. É necessário testar no Termux os casos: criação rápida, uso de capa da biblioteca, inclusão na galeria, remoção de vínculos, geração de backups completos e visualização pública do texto formatado.

## Compatibilidade e segurança

Sem alteração do esquema MySQL e sem mudança no formato de backup lógico v8. Fotos reutilizadas compartilham o mesmo arquivo em `public/uploads/`; o backup deve incluir essa pasta. Uploads novos mantêm validação existente; arquivos reutilizados são checados por caminho e MIME. HTML livre permanece escapado.
