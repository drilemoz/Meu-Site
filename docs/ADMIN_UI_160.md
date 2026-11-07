# Meu Site 1.0.60 — painel administrativo móvel

## Mudanças implementadas

- **Menu do painel:** um único drawer lateral **à direita**, ativado pelo ícone superior; grupos Notícias e Configurações abrem dentro do menu. A barra horizontal principal e as barras internas foram retiradas. Permissões por papel e URL antiga das rotas são mantidas; sair continua sendo POST com CSRF. O painel é responsivo no computador e no celular.
- **Notícias:** lupa ao lado de **+ Nova notícia** abre pesquisa/situação em janela. Um ponto indica filtro aplicado. Em telas pequenas, editar, duplicar, excluir e publicar/despublicar ficam lado a lado quando o usuário tem essas permissões; o status continua apresentado no cartão. Exemplos do editor ficam recolhidos.
- **Usuários e Eleições:** formulários de cadastro ficam recolhidos e abrem por ação; entrar por link de edição reabre o formulário com os dados correspondentes. URLs `#edit-user`, `#election-form` e `#candidate-form` continuam funcionando.
- **Mídias:** o gerenciador continua em tela cheia no Android, com cabeçalho fixo e envio de fotos recolhido, para priorizar a biblioteca. Upload automático e controles da galeria permanecem.
- **Confirmações do painel:** `data-confirm` é interceptado em fase de captura e apresentado por um `<dialog>` reutilizável. Cancelar ou fechar com Escape não pode executar ação. Mensagens de validação do upload passam pelo aviso personalizado. Em navegadores sem `<dialog>`, o fallback nativo é mantido por segurança. Backend continua aplicando ACL e CSRF; uma janela visual não substitui essas verificações.
- **Atualizador:** ZIP com mesma versão exibe “já está instalada”; ZIP mais antigo exibe “pacote antigo”. Ambos seguem rejeitados antes do backup e da cópia de arquivos. Quando um release remoto é da versão atual, o status informa versão mais recente.

## Restrições e validação

- Sem migrações de banco; `IGUA_SCHEMA_REVISION=36`. As alterações de layout não devem excluir notícias, usuários, mídias, arquivos privados ou configurações.
- CSS consolidado continua gerado de 15 módulos; novo JS `public/assets/js/admin-ui.js` apenas no painel.
- Testes: `php tests/admin_ui_160.php`, `node tests/admin_ui_160.js`, `python3 tools/build_css.py --check`, todos os testes PHP/JS existentes e lint PHP/JS.
- Sem MariaDB e servidor web Plesk no ambiente local, não há comprovação de funcionamento integrado ou renderização no Android físico. Valide principalmente navegação, edição, confirmações e uploads após instalar.
- O ZIP de atualização continua assinado e não inclui `storage/`, `public/uploads/` ou `public/ads.txt`.
