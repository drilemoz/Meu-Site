# Criação de banco de dados no instalador — versão 1.0.131

## Etapa 2 do `/setup.php`

O instalador oferece três caminhos de banco, sem modificar o modo de migração do portal nem o formato de backup:

1. **Criar banco automaticamente (usuário MySQL existente):** forneça host, porta, nome **novo** do banco, usuário e senha MySQL. O PHP conecta sem selecionar `dbname` e executa somente `CREATE DATABASE \`nome\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`. Se o nome já existir, falha e nunca executa `IF NOT EXISTS`, `DROP`, `TRUNCATE` ou altera tabelas. A aplicação continua usando a **mesma** conta MySQL informada, que deve ter acesso a esse banco e privilégios de criação de tabelas. Se essa conta tiver privilégios amplos, continue tratando-a como conta administrativa e prefira restringi-la após instalação.
2. **Criar banco e usuário exclusivo (avançado, MySQL local de VPS):** para banco em `localhost` ou `127.0.0.1` na mesma máquina que executa PHP. Requer um login MySQL com `CREATE DATABASE`, `CREATE USER` e `GRANT`. O sistema gera um nome de usuário aleatório `iguanews_...` e senha de alta entropia e concede somente privilégios necessários no banco novo, para as duas origens locais (`localhost` e `127.0.0.1`), sem `%`. As credenciais administrativas não são gravadas; só as credenciais da conta criada são persistidas como configuração privada do portal depois de instalar. Se a conta root só autenticar por socket, o fluxo pela web poderá não funcionar.
3. **Conectar a banco existente:** fluxo antigo, útil para cPanel/Plesk ou MySQL remoto com `CREATE DATABASE` proibido. Não executa criação de banco e exige uma base vazia, ou permite recuperação autenticada quando o inventário detecta instalação anterior válida.

No Termux local, o provisionamento anterior de banco e usuário continua disponível apenas quando o servidor PHP e o navegador estão em loopback. A criação em hospedagem usa caminhos específicos, com entrada sob HTTPS e CSRF. A conexão ao servidor MySQL remoto pode exigir TLS e liberação de firewall no próprio serviço: HTTPS no site não garante criptografia entre PHP e MySQL.

## Comportamento em falhas

- `CREATE DATABASE` é operação DDL não transacional. Falhas posteriores **não removem** o banco nem contas que já foram criadas. Se aparecer “banco criado, mas validação final falhou”, corrija as permissões e use **Conectar a banco existente**, sem recriar pelo mesmo nome.
- Criar o banco com sucesso não prova que a conta tem `CREATE TABLE`, `ALTER`, `DROP` e outras permissões necessárias ao portal: a instalação pode falhar na etapa de schema. Nesse caso, não instale por cima de um banco com dados.
- Em hospedagem compartilhada, a criação pode estar bloqueada pelo provedor. Sem `CREATE DATABASE` não é possível contornar a restrição pelo PHP. O formulário existente continua disponível.
- A criação do banco e a restauração portátil são etapas distintas. Ao escolher **Migrar meu portal**, o instalador ainda exige banco vazio, confirmação de um administrador antigo, hashes íntegros e validação do ZIP antes de restaurar.
- Não execute instalação com credenciais sensíveis por HTTP público. Não se devem guardar senhas ou contas de administração MySQL em ZIPs, repositórios, logs ou mensagens.

## Testes

- `php tests/installer_create_231.php`: criação SQL única, validação de nomes, recusa de banco existente e provisionamento restrito ao MySQL local.
- `php tests/installer.php` e `php tests/runtime_147.php`: compatibilidade do Termux, isolamento de acesso local e formulários.
- CI com MariaDB 11.4 isolado: `tests/integration/installer_create_mariadb_231.php` cria bancos de nome único, confirma que banco existente é recusado, cria usuário novo, verifica inserção/leitura/remoção de tabela e limpa SOMENTE objetos próprios daquele teste.
- Não extrapolar testes locais sem `pdo_mysql` para garantia de funcionamento na hospedagem. A disponibilidade real de privilégio `CREATE DATABASE` depende do provedor.


## Revisão de segurança 1.0.132

O instalador exige chave privada de autorização antes de aceitar credenciais. A criação simples separa a conta que cria bancos do usuário do portal, permitindo reutilização privilegiada somente com confirmação explícita. O banco totalmente vazio recebe verificação controlada de privilégios. Falhas parciais de criação são informadas sem DROP automático. MySQL remoto permite TLS com CA verificada; veja [Instalador seguro](INSTALADOR_SEGURO_132.md).
