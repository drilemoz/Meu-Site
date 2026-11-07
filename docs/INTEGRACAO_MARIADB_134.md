# Teste integrado de backup e recuperação · IguaNews 1.0.34

## Objetivo

Verificar em **MariaDB verdadeiro e PHP com `pdo_mysql` + `zip`**: instalação das tabelas, revisão do esquema, backup lógico v10, restauração de dados, transação/rollback sob falha, criação de ZIP v2, verificação de hash SHA-256 e recuperação física de fotografia.

**A versão 1.0.34 adiciona um executor de integração real e um workflow de GitHub Actions.** O ambiente em que o ZIP foi construído não contém MariaDB ou as extensões citadas, portanto o teste integrado **não foi executado aqui**. A configuração de CI permite executá-lo em uma cópia de trabalho após publicar o projeto em um repositório de sua confiança.

## GitHub Actions

O arquivo `.github/workflows/integration-mariadb.yml` configura MariaDB 11.4 temporário e executa o script `tests/integration/restore_mariadb_134.php` sobre um **banco novo do job**, sem credenciais reais. Execute o workflow **Integracao MariaDB backup e restore** na aba *Actions*, ou aguarde o disparo automático quando fizer push de alterações em `app/` ou `tests/`.

O workflow só pode comprovar resultado após a execução no GitHub. Não considere o teste aprovado se a execução não estiver verde.

## Termux / servidor local

Instale PHP com extensões `pdo_mysql` e `zip` e inicie MariaDB. Crie manualmente um **banco novo vazio** e uma conta de teste com permissões apenas nesse banco. Nunca aponte para o banco IguaNews em uso.

Exemplo de variáveis, substituindo com a conta de teste recém-criada:

```bash
export IGUA_TEST_ALLOW_WRITE=YES_ISOLATED_DB
export IGUA_TEST_DB_HOST=127.0.0.1
export IGUA_TEST_DB_PORT=3306
export IGUA_TEST_DB_NAME=igua_ci_backup134
export IGUA_TEST_DB_USER=usuario_de_teste
export IGUA_TEST_DB_PASSWORD='senha_de_teste'
php tests/integration/restore_mariadb_134.php
```

Requisitos de segurança antes de executar:

- O nome do banco deve começar com `igua_ci_` e ter apenas letras minúsculas, números e `_`.
- **O banco deve existir e estar vazio**. Se contiver tabelas, a execução é interrompida antes da instalação.
- A conexão deve ser em `127.0.0.1`, `localhost` ou `::1`.
- O script escreve e restaura dados de demonstração, incluindo foto temporária; nunca deve ser executado no banco de produção.
- O script não faz `DROP DATABASE`, não apaga usuários do MariaDB e não remove o banco de testes automaticamente, permitindo investigação em caso de falha.
- Gere uma instalação/cópia de código isolada para rodar o teste. O script grava arquivos temporários em `storage/backups/` e `public/uploads/` desse código e depois os remove.

## Casos exercitados

1. Instalação do esquema em banco novo e leitura da revisão atual.
2. Rodada adicional de migração idempotente.
3. Verificação de todas as tabelas do backup lógico v10.
4. Criação de usuário, categoria, notícia marcada como demonstração e PNG temporário.
5. Backup v10, alteração da notícia e restauração correta.
6. Tentativa de restaurar banco com FK inválida, exigindo rollback sem perda de dados.
7. Exportação ZIP v2 com banco e mídia e conferência do hash.
8. ZIP com banco adulterado e hash desatualizado é recusado sem alterar os dados.
9. ZIP com hash válido, mas FK inválida, deve provocar rollback de banco e exclusão da imagem nova que havia sido preparada.
10. ZIP válido recupera banco e imagem originalmente removida, conferindo o SHA-256 da foto.

## Limites e compatibilidade

Não muda a estrutura SQL, `IGUA_SCHEMA_REVISION` permanece 33, nem os formatos backup lógico v10 e pacote de mídias v2. O teste cobre apenas cenários controlados; restaurações sob falta de energia e falhas de armazenamento continuam não atômicas entre banco e filesystem. Não use testes como substitutos de backups externos ou de um ensaio com cópia representativa dos seus dados.
