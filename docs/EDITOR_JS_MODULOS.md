# Editor JavaScript modular · IguaNews 1.0.35

## Objetivo e limite

O JavaScript da edição de notícias foi separado em oito **arquivos de origem**. O navegador continua recebendo somente o arquivo `public/assets/js/news-editor.js` já montado, com os **mesmos 22.435 bytes e o mesmo SHA-256 da 1.0.34**. Essa etapa refatora a organização do código e **não altera a lógica executada**. Não são módulos ES independentes: todos compartilham o mesmo escopo léxico, na ordem explícita de montagem, mantendo as dependências e o comportamento original. A extração futura de interfaces explícitas entre módulos exige testes próprios.

| Origem | Responsabilidade |
|---|---|
| `01-blocks.js` | Blocos estruturados, templates e renderização |
| `02-steps.js` | Navegação de Básico → Texto → Foto |
| `03-rich-text.js` | Editor visual, conversão de texto e prévia |
| `04-cover-library.js` | Seleção da foto de capa e Biblioteca |
| `05-seo-navigation.js` | Validação entre etapas, SEO, slug e categoria sugerida |
| `06-media-metadata.js` | Prévia e preservação de legendas/créditos por seleção |
| `07-drafts-autosave.js` | Snapshot, recuperação e autosave com confirmação |
| `08-submit-init.js` | Validação final, envio e inicialização |

## Como editar

1. Altere o arquivo em `public/assets/js/news-editor-modules/` correspondente ao comportamento.
2. Execute `python3 tools/build_news_editor.py` no projeto.
3. Execute `python3 tools/build_news_editor.py --check` e `node tests/news_editor_modules_135.js`.
4. Execute também `node tests/editor_media_122.js`, `node tests/editor_draft_123.js` e `node tests/seo_url_124.js`.
5. Não edite diretamente o arquivo gerado `public/assets/js/news-editor.js` e não acrescente scripts para cada módulo ao HTML.

A distribuição do IguaNews não requer Python no Termux para servir o site; o JS compilado já está no ZIP. Na atualização, preservar armazenamento, banco, mídia e configuração. Os formatos de backup v10/v2 e revisão do esquema 33 permanecem.

## Validação que ainda falta

Teste manual com navegador Android (teclado, etapas, autosave com rede intermitente, prévia de imagens, colagem, edição e envio) e integração real com MariaDB no fluxo de edição. A equivalência byte a byte do JS não substitui esses testes.
