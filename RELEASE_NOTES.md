# Meu Site v1.0.79 — Correção do teste de índices MariaDB

- O workflow #6 falhou ao simular a migração 35→36 porque `news_indexes_mariadb_146.php` não importava os módulos de Publicidade, Editor e Métricas usados por `igua_migrate_schema()`.
- Corrigidos imports dos módulos de Publicidade, Editor, Métricas, login persistente e Eleições, com checagem de dependências antes do DDL no banco isolado.
- Regressão offline `tests/integration_bootstrap_179.php` evita que a mesma falha volte ao CI.
- O portal em produção, a estrutura MySQL 36, autenticação, backup e atualizador não tiveram lógica alterada.

**Validação**: testes locais de PHP e JavaScript, sintaxe, CSS/editor, auditoria e ZIP. Reexecutar o workflow GitHub Actions para validar MariaDB real nesta versão.
