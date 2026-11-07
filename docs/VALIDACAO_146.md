# Validação — Meu Site 1.0.46 (índices e EXPLAIN)

## Alterações

- **Esquema 36**: acrescenta os índices `idx_public_featured(status,featured,published_at,id)` e `idx_public_popular(status,views,published_at,id)` em `articles`. Eles correspondem à ordenação dos destaques e mais lidas com `status='published'`. Os índices originais e os relacionamentos continuam.
- `app/schema_migrations.php`: DDL aditivo e idempotente. Consulta `SHOW INDEX`, cria apenas o que falta e só grava a revisão após terminar todas as etapas. MariaDB efetua *autocommit* no `ALTER TABLE`; a migração é retomável, **não transacional**.
- `app/article_workflow.php`: no painel, `%`, `_` e `!` são escapados como literais, igual à busca pública. A pesquisa por trecho permanece `LIKE '%termo%'`; **não** foi convertida para FULLTEXT, que poderia omitir resultados e mudar o comportamento para expressões pequenas.
- `tools/news_explain.php`: diagnóstico de leitura, sem consulta às linhas de notícias e sem alterar dados. Executa `EXPLAIN` para recentes, destaques, mais lidas, busca literal e categoria secundária. Em MariaDB, executa com PHP e `pdo_mysql`.
- `.github/workflows/integration-mariadb.yml`: acrescenta integração em banco temporário `igua_ci_*`, incluindo uma migração simulada de revisão 35 para 36, verificação de índices, cinco planos reais e correspondência da pesquisa literal (os dados fictícios são revertidos em transação).

## Validação sem MariaDB

```sh
php tests/news_indexes_146.php
php tests/schema_migrations_133.php
php tests/schema_safety_138.php
for file in tests/*.php; do php "$file"; done
for file in tests/*.js; do node "$file"; done
python3 tools/build_css.py --check
python3 tools/build_news_editor.py --check
```

## EXPLAIN em banco existente (somente leitura)

```sh
# Conexão pelas variáveis IGUANEWS_DB_* ou storage/config.php
php tools/news_explain.php
```

A saída exibe índice escolhido (`key`), método de acesso (`type`), número estimado de linhas (`rows`) e detalhes (`Extra`). **Índice existente não significa índice utilizado**: o otimizador decide conforme tamanho do acervo e estatísticas; tabelas pequenas podem escolher varredura total. `EXPLAIN` não mede tempo real, latência HTTP ou memória. Compare as estimativas com consultas reais em banco de testes representativo, especialmente em acervos grandes.

A busca por trechos com `%` inicial não usa índice B-tree para localizar o termo. Para acelerar essa busca sem alterar resultados seria necessário projetar uma estratégia mais complexa (por exemplo índice de n-gramas com confirmação exata), com testes e custos de escrita. Não foi adicionada aqui.

## Antes da atualização em produção

1. Gerar backup lógico e preservar fotos, `storage/config.php` e pastas `storage/**` e `public/uploads/**`.
2. Confirmar que o usuário MariaDB dispõe de `ALTER TABLE` e `INDEX`. `ALTER TABLE` em tabela grande pode aumentar o uso de disco e bloquear operações de escrita conforme versão/engine/configuração; agende manutenção adequada e confira espaço livre.
3. Atualizar código, entrar no painel para executar a migração e confirmar a revisão 36 na Central de Saúde.
4. Executar `EXPLAIN` e medições reais no banco representativo; não pressupor melhoria percentual.

**Sem mudança de formato:** backup lógico v11, ZIP de mídias v2, URLs, paginação 12/20, filtros e editor preservados. Nenhum teste MariaDB foi executado no ambiente de geração do ZIP por ausência de `pdo_mysql` e servidor MariaDB; o CI foi preparado, não presumido aprovado.
