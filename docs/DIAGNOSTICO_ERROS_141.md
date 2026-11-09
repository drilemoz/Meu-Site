# Editor e diagnóstico de erros — v1.0.141

## O que mudou

1. **Fotografias:** a etapa 4 possui uma única entrada para a biblioteca. Dentro do seletor, tocar na imagem define a capa; `+ Na matéria` adiciona uma foto à galeria sem fechar o seletor. Upload direto do celular preserva as opções capa, várias fotos e ZIP. O formato persistido (`library_cover`, `library_gallery[]`, `image`, `gallery_files[]`, `photo_zip`) não mudou.
2. **Carregamento da biblioteca:** limite de 18 s para listagem, resposta JSON validada, falha de autenticação explícita, mensagem no lugar de carregamento infinito e botão Tentar novamente. Para uploads há 90 s, para operações POST comuns 30 s. Um timeout após upload não garante que o servidor não recebeu o arquivo: confira a biblioteca antes de reenviar.
3. **SEO:** corrigido ID duplicado no diagnóstico; botões locais e Gemini identificados. `Automático` é uma proposta local a partir de texto; `Gemini IA` consulta um serviço externo por ação explícita. O diagnóstico é local, não nota do Google. Recursos JS editoriais ganham versão na URL para evitar cache antigo.
4. **Gemini:** prazo máximo de espera de 38 s no navegador, cancelamento da requisição ao fechar, erros visíveis em vez de tela indefinida e registro privado do código técnico. O servidor limita sua consulta externa a cerca de 28 s. A sugestão não é aplicada sem confirmação, nem a notícia publicada.
5. **Publicação:** Rascunho / Publicar / Agendar movidos ao início da revisão, em linha no celular. Continua usando os mesmos valores e as mesmas verificações no servidor.
6. **Ferramentas → Diagnóstico de erros:** visão autenticada, apenas leitura, do ambiente PHP, disponibilidade da biblioteca e configuração local Gemini; eventos de saúde existentes e códigos técnicos de JS/AJAX. Uma falha de frontend envia só um código fixo, sem IP, URL, cabeçalhos, texto da notícia, chave ou stack. POST com CSRF e limite de 24 eventos/hora por sessão. Os arquivos em `storage/logs/` permanecem privados.

## Limitações e investigação

- Erros Nginx, kernel, MariaDB e PHP-FPM que não alcançam o PHP da aplicação **não** podem ser lidos integralmente pelo portal sem violar o isolamento. Na VPS use SSH e `journalctl -u php8.3-fpm`, logs Nginx, MariaDB e `systemctl status`, respeitando acesso restrito. Não exponha esses arquivos por HTTP.
- O erro real das capturas do editor só pode ser confirmado com a resposta HTTP da hospedagem, status da sessão e eventos novos. A correção da espera infinita mostra a falha e a registra, mas não presume causa externa.
- `Gemini IA` exige chave ativa em Integrações, PHP com cURL, DNS/HTTPS funcionais e disponibilidade/cota no modelo escolhido. Se permanecer indisponível, use **Integrações → Gemini → Testar** e a página de diagnóstico. Uma consulta em timeout pode ter consumido cota; não reenviar automaticamente.
- `PHP_WARNING` registra somente código, arquivo-base e linha. O handler padrão PHP continua ativo, sem expor mensagens ou alterar o comportamento. O monitor JavaScript registra apenas um evento de cada tipo por carregamento, no máximo 8.
- A checagem da biblioteca apenas amostra cinco arquivos recentes; não varre toda a pasta nem exclui arquivos órfãos. Upload e restauração devem ser testados em ambiente real.

## Validação

- `tests/editor_diagnostics_241.php` e `.js` verificam navegação, ações, auth/CSRF, códigos permitidos, privacidade e timeouts.
- Executar toda a suíte (`tests/*.php`, `tests/*.js`), `php -l`, geração CSS e auditoria. Testes com MariaDB, cURL e navegador em dispositivo físico continuam pendentes até execução na AWS.
- Schema 37 e backup v12 permanecem inalterados; não há DDL nesta atualização.
