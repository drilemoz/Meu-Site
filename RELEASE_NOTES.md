# Meu Site v1.0.78 — Integração MariaDB: teste eleitoral corrigido

- `elections_mariadb_141.php` importa explicitamente `app/schema_migrations.php` antes de consultar a revisão do banco.
- Regressão offline para checar o contrato de importação das migrações.
- Renderização da Central de Integrações no CI recebe dados fictícios completos, sem avisos de variáveis ausentes.
- Cabeçalho da Central exibe a versão atual dinamicamente.
- Autenticação, backups, uploads, central Google, atualizador e revisão SQL 36 preservados. Nenhuma alteração na estrutura do banco.

**Validação**: sintaxe, testes PHP/JavaScript, CSS/editor e integridade dos pacotes. A integração com MariaDB real e o workflow GitHub Actions devem ser executados novamente para confirmar a correção no ambiente CI.
