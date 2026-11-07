# Atualizações e recuperação — versão 1.0.77

## Pacotes

- `Atualizacao-vX.Y.Z.zip` atualiza **apenas o código** da hospedagem. Não contém `storage/`, `public/uploads/`, banco de dados, testes, documentação do projeto ou credenciais.
- `Meu-Site-vX.Y.Z-fonte-completo.zip` contém o código-fonte, scripts e testes; serve para GitHub/GitHub Manager, **não** para o formulário de instalação no painel.
- O formato `meu-site-update-v2` aceita ZIP sem assinatura. SHA-256 confere a integridade em relação ao manifesto **dentro do mesmo ZIP**, mas não prova a autoria do pacote. Use apenas um arquivo cuja origem seja confiável.

## Instalação pelo painel

1. Em **Configurações → Atualizações**, verificar a versão e criar backup externo do banco **e das mídias**.
2. Usar **Enviar atualização** (em celular: **Enviar ZIP**) ao lado de Releases.
3. Selecionar o ZIP, informar a senha, revisar, confirmar origem e iniciar upload.
4. A porcentagem mostrada até 100% mede **somente o envio do navegador**. Durante validação/backup/instalação no servidor, o indicador é indeterminado.
5. Conferir o resultado do PHP e o histórico. Se houver timeout, verificar a versão/histórico antes de reenviar, porque o servidor pode ter concluído o trabalho.

## Segurança e limitações

O servidor verifica versão, tamanho, caminhos, manifesto, CSRF e reautenticação; cria arquivos de segurança, journal e tenta rollback em caso de falha. O formato atual não executa migrações automáticas de banco: schema 36 deve ser preservado. Para uma mudança de schema, preparar processo assistido antes de publicar o pacote.

Uma atualização de código **não substitui** um backup. O restaurador de código e o backup de dados são fluxos distintos. Ver [BACKUP.md](BACKUP.md).

Teste de CI: `php tests/updater_modal_175.php`, `node tests/updater_modal_175.js`, `node tests/updater_progress_167.js` e `python3 tools/build_update_package.py --output <arquivo>`.
