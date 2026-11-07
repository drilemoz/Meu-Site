# IguaNews 1.0.13 · Validação

## Mudanças
- Filtros + busca + paginação de notícias (20 itens por página).
- Duplicação de notícia em rascunho, conservando marca de demonstração e autoria atribuída à conta atual; cópia de blocos e categorias, sem duplicar arquivos da galeria nem anexos.
- Preservação de slug em edições de título.
- Autosave agora reenvia quando houve falha e evita requisições concorrentes.

## Testes de código
- `php tests/article_workflow.php` para regras de filtro, slug, duplicação autorizada (verificação estática) e retry de autosave.
- `php tests/*.php`: suíte de regressão completa.
- `php -l` em todos os PHP e `node --check` em todos os JS.
- Inspeção do ZIP final e CRC de todos os arquivos.

## Testes pendentes
- Operações reais de duplicar e listar filtros com MariaDB no Termux (ambiente local de validação sem `pdo_mysql`).
- Testes de usabilidade em navegador Android, inclusive comportamento durante perda/retorno de rede.

## Compatibilidade
Sem alteração de tabelas. Backups v8 continuam iguais. Preserve todos os dados locais, galerias e credenciais antes da atualização.
