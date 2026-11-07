# IguaNews 1.0.19 | Ajustes responsivos do editor

## Mudanças
- Editor de notícias em tela única por padrão tanto na criação quanto na edição. Etapas opcionais continuam disponíveis.
- Prévia/SEO incluídos na tela contínua e texto com editor visual `contenteditable` sincronizado com o campo `body` em sintaxe de formatação simples já aceita pelo backend.
- Colagem de texto sem HTML de terceiros; prévia escapa HTML. Alternativa de edição em texto simples.
- Área de foto com três botões compactos: **Mídias**, **Limpar** e **Galeria**. Removido botão duplicado de escolha inserido por JavaScript.
- Upload direto de capa/galeria e anexos ainda disponíveis numa seção opcional. Nenhum campo `name` de upload ou vínculo foi removido.
- Prévia da capa reduzida e legenda mantida no campo específico em vez de ocupar imagem/cartão extenso.
- Seletor de arquivos consistente em painel e instalador, mantendo `input type=file` original para submissão.
- Listagem de notícias vira cartões no mobile, com ações compactas e sem arraste horizontal. Tabela preservada em telas grandes.

## Testes
- `php -l` em todos os arquivos PHP, `node --check` em todos os JS.
- Todas as suítes existentes em `tests/` e `tests/editor_119.php`.
- ZIP validado por CRC e sem arquivos privados do Termux.

## Limites da validação
- O ambiente de execução não tem `pdo_mysql` para teste integrado de MariaDB.
- Conferência final da ergonomia dos controles e de `execCommand` no navegador Android do usuário ainda pendente; a função é suportada em muitos navegadores, mas tem APIs legadas. A edição em texto simples foi mantida como alternativa.
- O formato de banco e backup lógico **v9** / pacote de mídia **v2** não mudou. Preservar pastas privadas e uploads ao atualizar.
