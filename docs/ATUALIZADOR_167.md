# Atualizações 1.0.67 — progresso do envio e respostas do servidor

## Funcionamento

O painel usa `public/assets/js/updater-progress.js` nos formulários de ZIP manual, simulação e instalação remota. Com suporte a `XMLHttpRequest` e `FormData`, o envio é feito por XHR, preservando o POST de sempre (CSRF, confirmação e senha administrativa). Quando o navegador não suporta essas APIs, o formulário usa envio HTTP convencional.

- O percentual exibido **durante o envio do ZIP** deriva de `XMLHttpRequest.upload` (`loaded / total`). Quando o navegador não informa `lengthComputable`, usa progresso indeterminado.
- Após concluir a transmissão local, a interface passa para **“Validando e instalando no servidor”**, com barra indeterminada. Isso não significa que a instalação terminou nem mede as fases de backup, substituição de arquivos ou rollback.
- No fluxo remoto (GitHub), a fase de download/instalação também é indeterminada: o navegador não tem acesso ao percentual do download executado pelo PHP.
- O navegador interpreta a mensagem do resultado HTML devolvido pela hospedagem; somente uma resposta com alerta de sucesso reconhecido pode gerar navegação automática de volta à tela. Falhas são exibidas sem alegar sucesso.
- Timeout, queda de rede ou resposta inesperada exigem conferir o estado na aba de Atualizações e seu histórico **antes de reenviar**. Não existe retry automático: a operação pode ter continuado no PHP após a desconexão.

## Segurança e armazenamento

O backend em `app/admin_controllers/updater.php` e `app/updater.php` mantém autenticação administrativa, CSRF, reautenticação por senha, confirmação da origem, limite de ZIP, verificação SHA-256, revisão 36 do MySQL, backup lógico, journal e rollback de arquivos. O frontend não substitui essas proteções. Pacotes ZIP v2 podem dispensar assinatura Ed25519; SHA-256 do manifesto dentro do próprio pacote não comprova autoria.

## Testes

- `php tests/ui_167.php`
- `node tests/updater_progress_167.js`
- `php tests/updater_162.php`
- Regressão completa das 66 suites PHP e 12 JavaScript: 78 aprovadas localmente
- `python3 tools/build_css.py --check`; validação sintática PHP e `node --check` no JavaScript.

**Pendente:** simular upload real com rede lenta e executar instalação completa em PHP-FPM, MariaDB e hospedagem. Um XHR com resposta 200 não fornece informação granular sobre as etapas de instalação.
