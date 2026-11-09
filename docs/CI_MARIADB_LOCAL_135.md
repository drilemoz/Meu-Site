# CI MariaDB local — correção do workflow 47 (v1.0.135)

## Evidência do defeito

No passo **Provisionamento MySQL local em banco isolado**, `tests/integration/installer_create_mariadb_231.php` gerou credenciais de usuário local `iguanews_...@localhost` e `@127.0.0.1`. O runner GitHub Actions conectou por `127.0.0.1:3306`, mas o Docker publicou a porta pela bridge. Dentro do MariaDB, o cliente apareceu como **`172.18.0.1`** e recebeu `SQLSTATE[HY000] [1045] Access denied`. Os testes de banco/ZIP anteriores haviam passado; o teste de atualizador nem chegou a ser executado.

## Correção sem afrouxar permissões

A etapa de CI usa `${{ job.services.mariadb.id }}` para selecionar o contêiner efêmero iniciado pelo próprio job e identifica seu PID por `docker inspect`. Depois executa `sudo nsenter --target <PID> --net -- php tests/integration/installer_create_mariadb_231.php`, entrando **somente** no namespace de rede do serviço, preservando o PHP, filesystem, usuário e código do runner. Assim `127.0.0.1` significa o loopback de onde o MariaDB está rodando.

Antes de criar objetos de teste, o script exige `SELECT USER()` vindo de `localhost`, `127.0.0.1` ou `::1`; sem isso, falha explicitamente. O job aborta se contêiner/PID estiverem inválidos ou se não houver autorização para `nsenter`. Nenhuma conta `%`, permissão remota ou modificação no código de provisionamento de produção foi adicionada. O banco e usuários de teste são gerados com nomes próprios e eliminados no `finally`.

## Validação

- `php tests/ci_mysql_local_235.php`: contrato do fluxo do job e origem local no teste.
- Testes PHP, JS, CSS/editor, sintaxe PHP, `tools/audit_project.py` e integridade SHA-256/CRC dos ZIPs.
- **Pendente:** enviar fonte 1.0.135 para o GitHub, executar o workflow completo e comprovar que tanto o provisionamento quanto o ciclo do atualizador concluíram. A disponibilidade de `sudo nsenter`/acesso ao namespace no runner precisa ser confirmada na execução real.

## Distribuição

O ZIP `Atualizacao-v1.0.135.zip` instala código e versão na hospedagem, mas não contém `.github/workflows/` nem `tests/`. Para corrigir o GitHub Actions, o repositório deve receber as alterações do **ZIP de código-fonte completo**. Não é necessário alterar o banco de produção, reiniciar a hospedagem ou migrar dados para esta correção.
