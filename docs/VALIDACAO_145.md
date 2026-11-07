# Validação — Meu Site 1.0.45 (consultas e paginação)

## Mudanças concretas

- `app/news_queries.php` concentra as consultas curtas do feed, destaque, ranking e existência de exemplos. São excluídos de listagens o `body MEDIUMTEXT` e as colunas editoriais não exibidas. Para abrir uma matéria, `public/index.php` ainda busca `a.*` intencionalmente.
- A ordenação dos mais lidos e do destaque passa a usar `a.id DESC` como último critério; o título escolhido em empates pode mudar, mas será estável.
- `app/news_archive.php` não prepara/executa SELECT de linhas quando não existem resultados e usa aritmética inteira na quantidade de páginas. Não foram alterados busca literal, categoria secundária, período, contagem, LIMIT/OFFSET ou links.
- `app/admin_views/routes/articles.php` conserva 20 resultados por página, mas recusa arrays/strings malformadas em `page` e `q`; evita SELECT com resultado zero.
- A consulta `is_demo` agora retorna apenas presença (0/1), suficiente para aviso e proteção contra indexação/publicidade; não é mais uma contagem de todos os exemplos.

## Comandos de verificação sem banco

```sh
php tests/news_queries_145.php
find app public tests -name '*.php' -print0 | xargs -0 -n1 php -l
for f in tests/*.php; do php "$f"; done
for f in tests/*.js; do node "$f"; done
python3 tools/build_css.py --check
python3 tools/build_news_editor.py --check
```

## Verificações que ainda precisam de banco real

Use exclusivamente **banco de teste isolado** com cópia anonimizada ou conteúdo fictício, nunca uma instalação de produção. Compare com `EXPLAIN` as consultas antigas e novas de destaque, ranking e feed; verifique `rows`, `key`, `Extra` e tempo real com quantidades variadas de matérias. Execute as integrações de MariaDB 11.4 disponíveis em GitHub Actions.

> A economia de RAM se refere especificamente ao não carregamento de colunas desnecessárias pelas consultas públicas. O ganho total depende de volume de dados e índices. A busca com `LIKE '%termo%'` ainda pode exigir varredura; indexação FULLTEXT não foi adicionada nesta revisão por mudar a semântica dos resultados. Páginas profundas ainda usam `OFFSET` e podem custar mais conforme o acervo cresce.

## Segurança e compatibilidade

- Mantidos esquema SQL 35, backup lógico v11, pacote de mídias v2, uploads, CSRF, permissões e URLs públicas/admin.
- Não sobrescrever `storage/config.php`, `storage/backups/`, `storage/media/` ou `public/uploads/` ao instalar; preservar backup anterior.
- Testes de HTTPS/HTTP real e renderização em navegador Android seguem pendentes.
