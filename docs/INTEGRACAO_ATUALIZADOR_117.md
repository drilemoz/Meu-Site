# Integração do atualizador com MariaDB e ZIP real — 1.0.117

## Objetivo e cobertura

A versão 1.0.117 acrescenta `tests/integration/updater_mariadb_217.php` ao workflow `.github/workflows/integration-mariadb.yml`. O novo teste roda **depois** do ensaio de restauração `restore_mariadb_134.php`, que cria o schema 37 no banco de integração local e prepara um administrador ativo.

A simulação usa **dois isolamentos independentes**:

1. **Banco MariaDB local `igua_ci_*`**, previamente criado pela configuração temporária do serviço GitHub Actions. A integração só lê dados de aplicação e registra o backup lógico de teste na tabela `backup_logs`. Não restaura, apaga ou migra tabelas.
2. **Projeto fictício em diretório novo dentro da pasta temporária do sistema**. `IGUA_ROOT`, `IGUA_STORAGE` e `IGUA_PUBLIC` apontam para esse diretório antes de carregar os módulos reais. O teste não utiliza o `app/bootstrap.php` publicado e não altera os arquivos do checkout nem da hospedagem.

A suíte exerce as funções reais `igua_updater_install_zip`, `make_backup`, `igua_updater_installed_integrity`, `igua_updater_manual_rollback` e a validação `ZipArchive` + manifesto SHA-256. Ela verifica:

- ZIP v2 real contendo `VERSION`, `app/updater.php`, `public/admin.php`, outro arquivo PHP e um JavaScript recém-criado;
- preflight, backup, aplicação e conclusão na ordem correta, com `VERSION` atualizado;
- todos os bytes instalados, ausência do marcador de manutenção e journal `complete`;
- arquivo de backup JSON/GZIP v12 válido e com todas as tabelas previstas;
- somente um novo registro em `backup_logs` e contagem de notícias inalterada;
- rollback manual restaurando os bytes anteriores, retirando o arquivo novo e concluindo como `rolled_back`;
- backup preservado após o rollback de código;
- ZIP com arquivo adulterado rejeitado antes de backup adicional, antes da manutenção e sem modificar código.

O teste também possui guardas de execução: exige PHP CLI, `IGUA_TEST_ALLOW_WRITE=YES_ISOLATED_DB`, nome do banco com prefixo `igua_ci_`, host local (`127.0.0.1`, `localhost` ou `::1`), usuário explícito e as extensões reais `pdo_mysql` e `ZipArchive`. O sandbox de arquivos é removido num `finally`. O banco de integração é administrado pelo serviço efêmero do workflow, não pelo teste.

## Execução no CI

O workflow instala `php-cli`, `php-mysql` e `php-zip`, inicializa um container de serviço MariaDB 11.4 e roda as integrações na ordem definida. O `updater_mariadb_217.php` é executado por último, após a restauração e as outras integrações com banco, com variáveis de ambiente limitadas à instância de CI.

Não executar esse teste no banco do site, nem apontar o host ou a raiz de arquivos para a hospedagem em produção. Caso queira reproduzi-lo fora do CI, use um banco **temporário dedicado**, preparado por `restore_mariadb_134.php`, usuário de teste e instância de banco local sem dados reais.

## Verificação offline e limites

`tests/updater_ci_contract_217.php` confere sem conexão que o workflow mantém a ordem, a autorização explícita, a pasta temporária e a ausência de comandos de restauração ou exclusão de banco no teste integrado.

A versão foi preparada num ambiente sem `pdo_mysql`, `ZipArchive` ou serviço MariaDB. Portanto, o novo ensaio integrado só pode ser considerado **executado** após um resultado bem-sucedido no GitHub Actions ou numa infraestrutura equivalente. Testes PHP locais e `php -l` não substituem essa execução.

Mesmo um CI bem-sucedido não comprova: falta real de espaço em disco, encerramento abrupto do PHP/FPM pelo Plesk, permissões particulares da BoaHost, OPcache ou a recuperação de toda a instalação publicada. Uma homologação independente e backup externo continuam obrigatórios antes de operações críticas.

## Compatibilidade

Não houve mudança no mecanismo do atualizador, no schema 37, no backup v12, no formato ZIP v2, nos arquivos privados ou nas permissões. A atualização 1.0.117 acrescenta testes e documentação; não dispara nenhum processo em produção.
