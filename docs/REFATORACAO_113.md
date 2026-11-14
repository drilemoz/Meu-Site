# Refatoração do Wizard de Notícias — 1.0.113

## Escopo e diagnóstico

O editor já usa oito módulos JS fonte (consolidados por `tools/build_news_editor.py`) e a view PHP é composta por oito parciais. O controlador POST `app/admin_controllers/news.php` era o principal ponto de concentração: validações de taxonomia, Gemini, autosave, importação URL, revisão, salvamento de artigos e ações de ciclo de vida.

## Divisão realizada

A entrada `app/admin_controllers/news.php` contém somente cinco inclusões literais, na ordem original de ações:

1. `news/taxonomy.php`: criar categorias e fontes com ACL.
2. `news/gemini.php`: sugestão opcional e limites de frequência.
3. `news/drafts.php`: autosave textual, importação de URL, revisão.
4. `news/save.php`: validação, publicação/agendamento, mídias, ZIP e transação.
5. `news/lifecycle.php`: duplicar, excluir e alternar publicação.

Esses arquivos são incluídos dentro do mesmo escopo PHP do controlador no `public/admin.php`. Não há carregamento por caminho recebido do navegador. O token CSRF é verificado antes do despacho POST, e os controles por papel e por notícia continuam nos mesmos pontos do código.

## Garantia de compatibilidade

- `tests/support/news_controller_source.php` recompõe o código legado de forma textual para testes estáticos, sem executar requisições.
- `tests/news_controller_refactor_213.php` confere SHA-256 do texto reconstruído contra a 1.0.112, as dez ações roteadas e as proteções contra rollback incorreto.
- Não se altera SQL, tabela, payload, dependências de frontend ou regras de autoria. Schema 37 e backup v12 não mudam.
- A equivalência de código não substitui testes com banco real e rede: após instalar, conferir criação de rascunho, publicação, upload individual/ZIP, edição com revisão, recuperação e Gemini sob ação.

## Limites

Não fragmentar a lógica de transação em helpers sem testes de falhas transacionais e de disco. Nenhuma informação real de notícias ou credenciais foi incluída nos pacotes.
