# Editor de notícias: controles legíveis e publicação (1.0.143)

## Problema identificado

- Os atalhos de Gemini no título/resumo e na barra de SEO usavam a classe global `igua-gemini-mark` no próprio `<button>`. A regra `-webkit-text-fill-color: transparent` eliminava também o desenho e a legenda quando o fundo gradiente do texto não aparecia: controles aparentemente vazios no Chrome Android.
- A barra de SEO tinha cinco ações em cinco colunas estreitas. Nomes como "Automático" quebravam/cortavam; a etapa de fotos usava três colunas mesmo tendo dois botões, cortando "Escolher da biblioteca".
- A publicação era apresentada como três opções de rádio e, para notícias novas, começava em "Rascunho". O botão efetivo estava no rodapé, separado da escolha.

## Correções

- A classe de gradiente agora aparece apenas no **glifo** `✦` dentro de um `<span>`. Os botões de título e resumo exibem o texto **Gemini IA**; o botão de SEO também o identifica. A janela e a chamada Gemini permanecem condicionadas ao clique do editor. O atalho na barra compacta de formatação mantém a etiqueta por acessibilidade.
- SEO utiliza três colunas na largura pequena e texto com quebra normal, sem cortar palavras. As ações da biblioteca usam duas colunas quando há espaço e uma coluna em telas estreitas; nenhum texto precisa caber em uma coluna de um terço da largura.
- No passo 9, o seletor nativo estilizado `publication_choice` e o botão `news-submit` ficam lado a lado, antes do resumo. Opções: `now`, `draft`, `scheduled`. A legenda do botão é atualizada quando a seleção muda, e o campo de data só aparece para agendamento.
- Para administradores e editores com permissão de publicar, a seleção inicial de uma notícia **nova ou rascunho** é "Publicar agora". Uma notícia já agendada preserva "Agendar". Redatores sem permissão recebem somente "Rascunho". O salvamento automático continua exclusivamente como **rascunho privado**, independentemente do destino escolhido no passo final.
- Não há publicação automática ao avançar, pré-visualizar, selecionar opção ou deixar o formulário: a submissão explícita do botão permanece obrigatória. O servidor mantém a checagem de `publication_choice`, ACL, data/hora futura, CSRF e os campos já existentes.

## Arquivos e testes

- Templates: `app/news_admin_view/header.php`, `blocks.php`, `details.php`, `media.php`, `footer.php`.
- CSS: `public/assets/css/modules/13-editor-mobile.css` recompilado em `style.css`.
- JavaScript: `02-steps.js` e `08-submit-init.js` recompilados em `news-editor.js`.
- Testes novos: `tests/editor_ui_243.php` (HTML gerado para administrador/redator/agendamento) e `tests/editor_ui_243.js` (comportamento do seletor, layout e ausência de gradiente aplicado ao botão). Testes existentes de Gemini e wizard foram atualizados para os controles legíveis.

## Compatibilidade e limites

- `VERSION=1.0.143`; schema 37, backup v12, formato dos dados e nomes de POST preservados. Não houve migração do banco nem alteração de permissões/VPS.
- A renderização real no Chrome Android e o envio de um formulário real na EC2 devem ser validados após atualização; testes locais/estáticos não comprovam execução em produção.
- Um indicador de autosalvamento que contenha a palavra "rascunho" **não** indica que a opção de publicação mudou. Ele informa apenas a cópia privada temporária; não publica conteúdo.
