# Atualizador 1.0.56 — origem e diagnóstico compactos

## Interface

O resumo agora apresenta dois botões compactos: **⚙ Fonte oficial** abre um `<dialog>` com o repositório, edição manual e a ação **Restaurar origem do projeto** quando há configuração personalizada. **✓ Servidor** abre outro `<dialog>` com os resultados da vistoria, diagnóstico completo e simulação de ZIP.

Ambas as janelas podem ser fechadas por **×**, tecla Esc ou toque no fundo. Não há novas bibliotecas nem serviços externos.

## Repositório oficial

O repositório `https://github.com/drilemoz/Meu-Site` foi conferido na conexão GitHub. `app/update-origin.json` guarda apenas `{"repository":"drilemoz/Meu-Site"}`. Configurações manuais anteriores continuam prevalecendo até restauração explícita. O próprio servidor PHP não tem acesso à conta GitHub conectada ao ChatGPT; ele consulta apenas os Releases públicos. Quando o repositório não tem um Release `vX.Y.Z`, a tela não anuncia uma versão disponível e a resposta 404 é cacheada por 30 minutos. Para atualizar automaticamente pelo GitHub, publicar um Release com ZIP `Atualizacao-vX.Y.Z.zip`, assinado com a chave privada externa preservada.

## Rodapé

No celular, a regra anterior reservava 105px em `.footer-new` além dos 74px de `.site-footer`. A versão 1.0.56 reduz o recuo interno e deixa a `.site-footer` com espaço de `68px + env(safe-area-inset-bottom)` para a barra fixa. Requer validação visual em Chrome Android, incluindo telas pequenas e barra de navegação do sistema.

## Segurança e testes

A assinatura Ed25519 dos pacotes, SHA-256, CSRF, papéis administrativos, backup e controle de versão não mudaram. A restauração continua exigindo senha. Sem migração SQL (revisão 36).

Verificação: `php tests/updater_156.php`, demais testes PHP, JS, `python tools/build_css.py --check` e assinatura do ZIP. Ainda pendente o teste físico e uma instalação real na BoaHost.
