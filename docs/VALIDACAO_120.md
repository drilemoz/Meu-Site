# IguaNews 1.0.20 | Ajuste do fluxo de notícias

## Alterações em código

- `app/news_admin_view.php`: remove modo contínuo e etapa independente de SEO; adiciona 3 botões de navegação, progresso acessível e move SEO/destaque para opções de Básico.
- `public/assets/js/news-editor.js`: passo a passo por padrão, valida manchete/categoria e conteúdo antes de avançar, botão Salvar apenas no terceiro passo, preserva autosave e descrição SEO automática.
- `public/assets/css/style.css`: navegação de 3 colunas e alinhamento responsivo de botões no Android.
- `tests/editor_119.php`: adapta checagens históricas ao fluxo atual sem dispensar as regressões de editor visual, mídia e upload.
- `tests/editor_120.php`: cobre estrutura de três etapas, ausência da quarta tela, opções de SEO, validações, envio de imagens e botões.

## Compatibilidade

Não há ALTER TABLE, alteração de backup lógico v9, do pacote de mídias v2 ou exclusão de campos de notícia. `storage/`, `public/uploads/` e backups existentes devem ser preservados na atualização.

## Validação e limitações

Executar `php -l` em todos os arquivos PHP, `node --check` para JS e `php tests/*.php`. Testes estruturais não substituem simulação real de toque, preenchimento e upload num navegador Android com MariaDB ativo; esses fluxos ainda precisam ser verificados no Termux.
