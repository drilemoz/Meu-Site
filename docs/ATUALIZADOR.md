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

## Migração assistida na v1.0.96 (schema 37)

A atualização automática via GitHub e o envio de ZIP pelo painel da 1.0.95 ou anterior **não podem fazer a migração 36→37**: o preflight rejeita o pacote corretamente. **Não desative esta verificação** e não altere o manifesto para anunciar schema 36. Faça backup externo completo (banco e mídias), use ambiente de homologação e envie os arquivos de código via gerenciador/SFTP, preservando `storage/`, configurações e `public/uploads/`, e então acesse como administrador para executar as migrações idempotentes no primeiro carregamento. O usuário do MariaDB precisa de `CREATE TABLE`/`ALTER TABLE`. Confira o marcador de schema `37`, as quatro tabelas, a Central de Saúde, as notícias anteriores e as URLs `/uaicortes` (404 até publicação). Se ocorrer erro, restaure juntos código + banco + mídias do snapshot anterior; não use só rollback de código com banco migrado. Para passos detalhados consulte [APLICATIVOS_LEGAIS.md](APLICATIVOS_LEGAIS.md).

## Segurança e limitações

O servidor verifica versão, tamanho, caminhos, manifesto, CSRF e reautenticação; cria arquivos de segurança, journal e tenta rollback em caso de falha. O formato atual não executa migrações automáticas de banco: o schema do ZIP deve coincidir com o instalado. A v1.0.96 é schema 37 e sua primeira instalação exige migração assistida, conforme acima.

Uma atualização de código **não substitui** um backup. O restaurador de código e o backup de dados são fluxos distintos. Ver [BACKUP.md](BACKUP.md).

Teste de CI: `php tests/updater_modal_175.php`, `node tests/updater_modal_175.js`, `node tests/updater_progress_167.js` e `python3 tools/build_update_package.py --output <arquivo>`.

## Novas versões disponibilizadas automaticamente no GitHub

Após um envio com nova `VERSION` para `main` pelo GitHub Manager, o workflow de integração MariaDB verifica o código e publica, quando aprovado e `VERSION` alterado, a Release `vX.Y.Z` com o único pacote destinado à hospedagem `Atualizacao-vX.Y.Z.zip`. O painel consulta a API de `releases/latest` para `drilemoz/Meu-Site` e usa cache de 30 minutos. Se houver uma versão superior à instalada, mostra a opção de obtê-la. Uma Release de versão igual à instalada não é exibida como novidade.

O processo GitHub é automático; **instalar continua sendo uma decisão do administrador**. Não confundir o ZIP de código-fonte com o ZIP de atualização. Não confundir SHA-256 com assinatura de autoria: é necessário confiar na conta/repositório que publicou.

## Diagnósticos da versão 1.0.97

O botão **Visualizar** classifica os erros da consulta remota em códigos não sensíveis: `curl_missing`, `dns`, `tls`, `timeout`, `github_limited`, `github_http`, `github_remote_error`, `storage` ou `unknown`. Esses códigos são hipóteses baseadas na falha real do PHP, não prova de que o servidor tenha completado qualquer instalação.

O botão **Diagnosticar ZIP antes de instalar** envia o arquivo apenas para validação de leitura (`operation=diagnose_zip`, POST + sessão de administrador + CSRF). Ele avalia o manifesto e, quando compatível, realiza preflight sem gravar no MySQL nem substituir código. A confirmação de instalação depende de senha e seleção posterior.

**Bloqueio obrigatório:** a versão 1.0.96 introduziu schema 37. Pacotes que cruzam schema 36→37, inclusive a atualização 1.0.97 usada num portal ainda na 1.0.95/schema 36, precisam de **instalação assistida** com backup externo. Não force a aplicação via ZIP comum, mesmo que a release esteja disponível no GitHub.

## Segurança de rollback na 1.0.115

- Antes de restaurar, o atualizador verifica o journal e **cada snapshot**. Uma cópia faltante/adulterada bloqueia a operação antes da primeira troca de arquivo.
- Na recuperação manual, um marcador coloca o portal em manutenção e o journal muda para `recovery_needed` antes de começar a restauração. Uma queda durante a restauração deixa o processo recuperável em nova tentativa autenticada; não reenvie uma instalação de resultado incerto.
- O marcador de manutenção precisa ser realmente removido para informar êxito; em caso de erro ou estado inconsistente, faça recuperação assistida. Nunca apague `storage/updater/jobs` ou `.update-maintenance` sem examinar o journal, os snapshots e backups externos.
- Testes com interrupções simuladas ocorreram **somente em arquivos temporários no CLI**. Ainda é necessário reproduzir em homologação com MariaDB e ZipArchive. Ver [RECUPERACAO_FALHAS_115.md](RECUPERACAO_FALHAS_115.md).

## Proteção contra modificações externas na 1.0.116

- Jobs interrompidos (`prepared`, `applying`, `recovery_needed`) só podem restaurar quando **todos os destinos** ainda estão no estado antigo ou novo registrado. Arquivo inesperadamente alterado/ausente bloqueia o processo, sem sobrescrever mudanças do proprietário.
- A verificação ocorre antes de modificar qualquer arquivo de destino; a retomada legítima de rollback parcial continua permitida.
- Ver [ROLLBACK_EXTERNO_116.md](ROLLBACK_EXTERNO_116.md) e executar `tests/updater_foreign_changes_216.php`. Os testes são offline, sem MariaDB/ZipArchive reais no ambiente local.

## VPS AWS EC2 (1.0.138)

O modo `IGUA_DEPLOY_VPS=1` desabilita a troca direta de código por PHP-FPM. O painel registra pedido ao worker systemd dedicado; o ZIP oficial do GitHub é validado, backups do código/MySQL ocorrem antes da aplicação e o progresso é consultado por usuário/token. Recuperação de código na VPS é por SSH. Confira [AWS EC2](AWS_EC2_DEPLOY.md).

### EC2 1.0.139: estado e permissões da conta de implantação

No modo `IGUA_DEPLOY_VPS=1`, o marcador de manutenção fica em `storage/updater/vps/private/maintenance` e o lock de código em `storage/updater/vps/private/.code.lock`. O diretório privado pertence ao usuário `iguanews-deploy`, com acesso de grupo apenas para leitura/travessia. O marcador legado `storage/.update-maintenance` também continua bloqueando novas atualizações até recuperação assistida. Não remova nenhum marcador nem journal sem apurar a causa.

O teste de prontidão real executa `bin/aws-deploy-check.php` como usuário de implantação e verifica criação/renomeação/exclusão em root, app, public, backup, jobs e área privada. A vistoria web lê o resultado recente, mas não executa essas operações como `www-data`. Consulte [procedimento EC2](AWS_EC2_DEPLOY.md).
