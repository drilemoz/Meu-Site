# Atualizações e recuperação — versão 1.0.92

## Pacotes

- `Atualizacao-vX.Y.Z.zip` atualiza **apenas o código** da hospedagem. Não contém `storage/`, `public/uploads/`, banco de dados, testes, documentação do projeto ou credenciais.
- `Meu-Site-vX.Y.Z-fonte-completo.zip` contém o código-fonte, scripts e testes; serve para GitHub/GitHub Manager, **não** para o formulário de instalação no painel.
- O formato `meu-site-update-v2` aceita ZIP sem assinatura. SHA-256 confere a integridade em relação ao manifesto **dentro do mesmo ZIP**, mas não prova a autoria do pacote. Use apenas um arquivo cuja origem seja confiável.

## Instalação pelo painel

1. Em **Configurações → Atualizações**, confira a versão e mantenha backup externo do banco e das mídias.
2. Clique em **Visualizar** para conectar ao GitHub e consultar a última release e as notas de mudanças, sem sair da página.
3. Se houver atualização com ZIP, confirme a origem e informe a senha administrativa no modal para iniciar a instalação. Configuração da origem e vistoria ficam em **Configurações e diagnóstico**, sem cartões na tela principal.
4. O progresso remoto apresenta **apenas as etapas escritas pelo PHP** (download, manifesto, preflight, backup, arquivos). Não há porcentagem real para a execução do servidor, portanto a barra permanece indeterminada até confirmação da conclusão. Durante manutenção a consulta de status pode ficar indisponível temporariamente.
5. Após a resposta de sucesso do PHP, a janela exibe **Atualização concluída** e o botão **Atualizar página**.
6. Como alternativa, clique em **Enviar ZIP** para o modal de envio manual, com revisão/consentimento e progresso **somente dos bytes enviados** pelo navegador.
7. Se houver timeout, erro de rede ou resposta inconclusiva, confira a versão instalada e o histórico **antes** de reenviar. O servidor pode ter concluído a instalação mesmo sem resposta no navegador.

### Consulta de progresso

- A verificação de nova release e a leitura do estágio usam GET autenticado sob a capacidade `manage_settings`.
- A leitura do estágio exige token hexadecimal aleatório de 128 bits, associado ao ID administrativo; o servidor grava apenas o nome da etapa e uma mensagem estática em `storage/updater/` (privado).
- Durante o POST de atualização remota, o PHP libera o bloqueio da sessão somente após autorizar senha/CSRF, e o retoma antes de registrar a mensagem de sucesso/erro.
- Download de release e backup podem levar tempo; falha de consulta de status não significa falha de instalação. A tela evita mostrar porcentagens não fundamentadas.

## Segurança e limitações

O servidor verifica versão, tamanho, caminhos, manifesto, CSRF e reautenticação; cria arquivos de segurança, journal e tenta rollback em caso de falha. O formato atual não executa migrações automáticas de banco: schema 36 deve ser preservado. Para uma mudança de schema, preparar processo assistido antes de publicar o pacote.

Uma atualização de código **não substitui** um backup. O restaurador de código e o backup de dados são fluxos distintos. Ver [BACKUP.md](BACKUP.md).

Teste de CI: `php tests/updater_modal_175.php`, `node tests/updater_modal_175.js`, `node tests/updater_progress_167.js` e `python3 tools/build_update_package.py --output <arquivo>`.

## Novas versões disponibilizadas automaticamente no GitHub

Após um envio com nova `VERSION` para `main` pelo GitHub Manager, o workflow de integração MariaDB verifica o código e publica, quando aprovado e `VERSION` alterado, a Release `vX.Y.Z` com o único pacote destinado à hospedagem `Atualizacao-vX.Y.Z.zip`. O painel consulta a API de `releases/latest` para `drilemoz/Meu-Site` e usa cache de 30 minutos. Se houver uma versão superior à instalada, mostra a opção de obtê-la. Uma Release de versão igual à instalada não é exibida como novidade.

O processo GitHub é automático; **instalar continua sendo uma decisão do administrador**. Não confundir o ZIP de código-fonte com o ZIP de atualização. Não confundir SHA-256 com assinatura de autoria: é necessário confiar na conta/repositório que publicou.
