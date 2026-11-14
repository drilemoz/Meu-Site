# Instalador passo a passo — v1.0.133

## Fluxo das quatro telas

1. **Requisitos**: mostra os requisitos e oferece **Avançar** no início e no final. O botão fica desativado quando há requisito obrigatório pendente; PHP também impede passar para outras telas enquanto não estiver pronto.
2. **Modo**: decisão separada entre instalação limpa e migração; botões **Voltar/Avançar**. Quando existe backup portátil embutido, a instalação limpa permanece bloqueada pelas regras no servidor.
3. **Banco**: mantém os quatro fluxos de v1.0.132 (conexão existente, criação simples, criação exclusiva em VPS, Termux). Em navegadores com JavaScript, cada campo aparece isolado, com host, porta, banco, usuário, senha, opções avançadas e revisão antes do POST. Sem JS, o formulário completo original permanece utilizável.
4. **Conteúdo**: criação de identidade e conta administrativa, migração portátil, importação legada e recuperação de configuração são guiadas campo a campo. Na migração continua obrigatória a autenticação administrativa antiga. O envio de backup em partes passa a usar a URL de etapa 4.

## Senhas e navegação

- Qualquer input `type=password` recebe **Mostrar/Ocultar**; a senha nunca é mostrada no resumo, mesmo se o campo estiver temporariamente visível.
- **Senha nova do administrador do portal**: mínimo de 10 caracteres; medidor informativo de força (fraca/média/forte), contador de caracteres restantes e campo obrigatório de confirmação. O PHP exige o mínimo e usa `hash_equals` para verificar a confirmação, independentemente do JavaScript.
- **Senhas existentes de MySQL e do administrador antigo**: apenas Mostrar/Ocultar, sem exigir confirmação ou medidor, pois o instalador não as cria.
- **Voltar/Avançar** funcionam nos formulários guiados sem descartar os valores digitados. Antes de executar, a revisão mostra a configuração em texto, mas oculta credenciais e senhas. Formulários originais preservados para casos sem JavaScript.
- A barra de progresso informa somente a etapa de preenchimento. Não equivale a progresso percentual real de criação de tabelas ou extração de arquivos.

## Limites e segurança

A chave privada inicial da 1.0.132, autorização de migração, HTTPS, CSRF, TLS MySQL, banco vazio, limites de ZIP e tratamento de erro permanecem no backend. Não há mudança no schema 37 nem no backup lógico v12. Esta versão não altera produção automaticamente nem substitui teste de instalação real em MariaDB/ZipArchive e no Chrome Android.

Testar: `tests/setup_wizard_233.php`, `tests/setup_wizard_233.js`, `tests/setup_backup_upload_150.js`, `tests/installer_hardening_232.php`, suíte PHP/JS completa, `tools/build_css.py --check` e `tools/audit_project.py`.
