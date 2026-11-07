# IguaNews v1.0.32: diferenças e inventário de linhas

Versão-base: **1.0.31**. Release: **1.0.32**.

## Modificações

| Arquivo | Situação | Linhas atuais | Linhas adicionadas | Linhas removidas |
|---|---|---:|---:|---:|
| `AGENTS.md` | Alterado | 75 | 2 | 0 |
| `CHANGELOG.md` | Alterado | 256 | 6 | 0 |
| `README.md` | Alterado | 351 | 4 | 0 |
| `VERSION` | Alterado | 1 | 1 | 1 |
| `app/news_editor.php` | Alterado | 15 | 13 | 358 |
| `app/news_editor/blocks.php` | Novo | 48 | 48 | 0 |
| `app/news_editor/format.php` | Novo | 46 | 46 | 0 |
| `app/news_editor/import.php` | Novo | 87 | 87 | 0 |
| `app/news_editor/library.php` | Novo | 54 | 54 | 0 |
| `app/news_editor/media.php` | Novo | 77 | 77 | 0 |
| `app/news_editor/revisions.php` | Novo | 36 | 36 | 0 |
| `app/news_editor/schema.php` | Novo | 53 | 53 | 0 |
| `docs/VALIDACAO_132.md` | Novo | 23 | 23 | 0 |
| `tests/editor_modules_132.php` | Novo | 35 | 35 | 0 |
| `tests/editor_revision_132.php` | Novo | 53 | 53 | 0 |

## Decisões

- `app/news_editor.php` passa a carregar sete módulos locais com `require_once`, mantendo a mesma API de funções públicas.
- Blocos, importação, mídia, biblioteca, formatação, esquema e revisões ficam separados por responsabilidade.
- O campo `is_demo` não é alterado ao restaurar revisões, evitando a publicação de amostras como notícias reais.
- Banco de dados e formatos de backup **não foram alterados**.
- Arquivos operacionais e privados em `storage/` não entram no ZIP; a instalação existente deve preservar dados e imagens.

## Testes locais

- **86/86** sintaxes PHP válidas.
- **14/14** JavaScript sintaticamente válidos.
- **30/30** suítes PHP, **5/5** suítes JS e **8/8** cenários HTTP aprovados.
- Integridade do CSS modular verificada, sem alterações.
- MariaDB real e Android físico **não testados** nesta sessão.

## Inventário

214 arquivos inspecionados, com caminho, linhas, bytes e SHA-256 no CSV anexo. O inventário exclui seus próprios arquivos para evitar referências circulares.
