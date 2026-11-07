# IguaNews 1.0.23 | Recuperação fiel dos rascunhos editoriais

## Alterações

- `public/assets/js/news-editor.js`: snapshot exclui CSRF, ID, blocos já serializados, arquivos e legendas/créditos temporários de uploads que ainda não foram enviados. As seleções de arquivo continuam exclusivas do salvamento explícito.
- A restauração preserva a ordem dos valores de campos repetidos e reconstrói grupos de checkboxes, marcando ou desmarcando exatamente conforme o rascunho. Campos de upload e IDs do artigo não são restaurados.
- Recuperação de dados inválidos é tratada com aviso, sem interromper o editor.
- `app/news_admin_view.php`: informa que arquivos selecionados no aparelho não fazem parte do autosave.
- `tests/editor_draft_123.js`: teste de comportamento de serialização e recuperação por meio do código JavaScript real, com DOM/FormData simulados.
- `tests/editor_123.php`: regressões estruturais de integração e três etapas.

## Compatibilidade

- Banco de dados, tabela `article_drafts`, formato de payload `{fields,blocks}`, URLs e rotas continuam inalterados.
- Rascunhos antigos com campos escalares e listas são lidos. Metadados de arquivos temporários antigos não são recuperados, já que os próprios arquivos não podem ser restaurados.
- Editor continua **Básico → Texto → Foto**, backups lógicos **v9** e mídias **v2**, sem migração SQL.
- Não altera nem empacota `storage/config.php`, `storage/analytics.key`, `storage/backups/`, `storage/media/` ou uploads reais.

## Verificação

Executar a partir da raiz do projeto:

```sh
find app public tests bin -name '*.php' -type f -exec php -l {} \;
find public/assets/js tests -name '*.js' -type f -exec node --check {} \;
for f in tests/*.php; do php "$f" || exit 1; done
node tests/editor_media_122.js
node tests/editor_draft_123.js
```

## Pendências

- Recuperação real em Chrome Android, alternando as três etapas e verificando campos após recarregar.
- Teste de autosave e edição em MariaDB/Termux com perda simulada de conexão.
- Nenhuma execução de migração, restauração de backup ou upload de mídia real foi realizada nesta validação local.
