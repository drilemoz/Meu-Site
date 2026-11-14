# Experiência pública — v1.0.148

Esta atualização modifica **somente a experiência do visitante**, não as rotas, formulários, serviços ou mecanismos administrativos.

## Melhorias aplicadas

1. **Compartilhar matérias:** na página de uma notícia publicada, dois botões discretos permitem compartilhar por aplicativo (Web Share API no Android) ou copiar o link. Sem Web Share, o botão Compartilhar recorre à cópia; se a área de transferência estiver indisponível, um campo selecionável exibe o endereço. Nenhuma chamada externa é feita pelo compartilhamento sem ação do usuário. O endereço é gerado pela rota da notícia, validado para permanecer no mesmo domínio.
2. **Navegação por teclado:** o link "Ir para o conteúdo" só aparece ao receber foco. A área `<main>` recebe identificador e foco programático. Links e botões públicos ganham indicação de foco visível. O menu móvel pode ser fechado por Escape ou toque fora dele, atualizando a descrição acessível e devolvendo foco ao botão após Escape.
3. **Carregamento visual:** imagem principal da home e capa da matéria recebem prioridade de download, com decodificação assíncrona; miniaturas abaixo da dobra continuam carregadas sob demanda e também são decodificadas de modo assíncrono. Isso é uma orientação de carregamento para navegadores que a suportam, não uma alegação de ganho medido em produção.

## O que foi preservado

- Sem novo schema, SQL, configuração AWS, intervenção no GitHub, mudanças no ADM, horários de ônibus, notícias publicadas, editorias ou SEO editorial.
- O formulário de busca e a página de ônibus da v1.0.147 permanecem.
- `app/public_views/routes/article.php`, `app/public_views/routes/home.php`, `app/public_views/layout.php`, `public/index.php`, `public/assets/js/main.js`, `public/assets/js/article-share.js` e `public/assets/css/modules/05-site-layout-contact.css` contêm a implementação da experiência pública.

## Testes e limites

- `python3 tests/public_ux_248.py` valida ações em Chromium sem servidor e contratos estáticos, quando o navegador de testes estiver disponível.
- `node --check public/assets/js/article-share.js`, `php -l` dos arquivos públicos, `python3 tools/build_css.py --check` e regressão existente verificam sintaxe e estrutura.
- A validação real no Chrome Android, na EC2, e métricas Core Web Vitals permanecem pendentes. A verificação automatizada não representa o site ao vivo.
