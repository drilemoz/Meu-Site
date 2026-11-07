# Atualizador v1.0.75 — interface de envio e instalação

## Acesso

Painel → Atualizações → **Enviar atualização** ao lado de **Releases**. No celular o texto pode aparecer como **Enviar ZIP**. Esse botão abre um `<dialog>` centrado, sem expandir o conteúdo da página.

## Fluxo manual

1. Selecionar `Atualizacao-vX.Y.Z.zip` e informar a senha administrativa.
2. Pressionar **Enviar**. Ainda não houve transmissão.
3. Revisar o nome do ZIP, marcar a confirmação de origem confiável e pressionar **Confirmar e enviar**.
4. A interface mostra porcentagem real e bytes enviados até a hospedagem. O indicador não mede o backup ou a instalação.
5. Após o upload, a barra torna-se indeterminada, informando que o PHP está validando e instalando. O diálogo não fecha até resposta ou erro.
6. A resposta oficial é exibida dentro do modal. **Ver resultado** abre o painel e histórico. Em erro confirmado é possível **Rever dados**; erro de rede ou timeout não gera reenvio automático porque o servidor pode continuar executando.

## Segurança e limitações

O ZIP v2 sem assinatura não comprova autoria apenas com SHA-256. A senha e a confirmação administrativa continuam obrigatórias, assim como CSRF, comparação de versão, limites de tamanho/entrada, cópia de segurança, journal e rollback. O painel não mede etapa por etapa no servidor e não deve exibir uma porcentagem fabricada para essas fases. A primeira instalação dessa versão ainda utiliza o formulário da versão anterior. O navegador da hospedagem e o PHP-FPM precisam ser testados antes de afirmar comportamento em produção.

## Testes

`php tests/updater_modal_175.php`, `node tests/updater_modal_175.js`, `node tests/updater_progress_167.js`, `python tools/build_css.py --check`, regressões e integração real na hospedagem.
