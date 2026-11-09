# Galeria de fotografias das matérias — 1.0.134

## Fluxo do leitor

- Abaixo da matéria, a seção **Galeria de fotos** aparece apenas se existirem fotos anexadas. O cabeçalho informa o número real de fotos.
- As miniaturas usam `news_media_thumbnail()`; o visualizador usa `media_url()` para carregar a imagem inteira somente após interação.
- Ao tocar/clicar, a imagem é ampliada num `<dialog>` nativo e apresenta legenda, crédito, posição e o link **Abrir original**. As setas laterais, as teclas Esquerda/Direita e gesto horizontal alternam fotos. Escape ou o botão Fechar volta à matéria e ao item que estava em foco.
- Com JavaScript desligado ou em navegadores sem `HTMLDialogElement.showModal()`, a foto ainda abre pelo link original em nova aba. Tecla modificadora e clique com botão central também preservam o link nativo.
- Se a imagem inteira não carregar, o visualizador informa indisponibilidade. Não cria, remove ou renomeia arquivos da biblioteca.

## Segurança, acessibilidade e desempenho

- Título, legenda e crédito são escapados por `h()` no HTML. No JS, dados da galeria entram por `textContent`/`alt`, nunca por `innerHTML`.
- `dialog` fornece foco modal e Escape nativos. Controles são botões de pelo menos 44 px, com rótulos, foco visível e retorno ao link anterior. Imagens têm textos alternativos; o aviso de falha usa região `role=status`.
- Movimento das miniaturas é desativado quando o sistema informa preferência por movimento reduzido. Miniaturas usam `loading=lazy` e `decoding=async`.
- O CSS modular é `public/assets/css/modules/07-editor-structured.css`, consolidado em `public/assets/css/style.css`. JS apenas nas matérias: `public/assets/js/article-gallery.js`.

## Validação

Executar `php tests/gallery_public_234.php`, `node tests/gallery_public_234.js`, regressão completa, lint, validação de CSS e auditoria. Reproduzir uma matéria com zero, uma e várias fotos no Chrome Android, verificando vertical/horizontal, crédito, Escape, swipe, foco e imagem ausente. Não há alteração de schema 37 nem backup v12.
