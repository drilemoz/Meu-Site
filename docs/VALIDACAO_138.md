# IguaNews 1.0.38 — Validação da refatoração de ônibus e migrações

## Escopo
- Extrair POST e visualização de `app/bus_admin.php` para cinco módulos privados; função pública e controles mantidos.
- `igua_migrate_schema()` agora interrompe quando a revisão gravada for futura; `igua_schema_revision()` rejeita marcadores não inteiros, negativos, vazios ou acima de `PHP_INT_MAX`.
- Sem alteração de DDL, esquema 33, URLs, esquema do backup lógico v10 ou mídias v2.

## Verificações
- `php tests/bus_admin_138.php`: SHA-256 da renderização de linhas e importação, com objetos PDO simulados, idêntico à versão anterior; dez ações mapeadas e autorização central.
- `php tests/schema_safety_138.php`: recusa de revisão futura sem DDL ou escrita e tratamento de marcador inválido.
- `php tests/schema_migrations_133.php`: revisões normais e repetição após falha.
- `php tests/smoke.php` e demais testes da suíte.
- Testes de integração MariaDB e validação visual no Android **não executados neste ambiente**; exigem execução em base de testes isolada.

## Recomendações
- Faça backup do banco e imagens antes de atualizar o Termux; não substitua o diretório `storage/` ou `public/uploads/`.
- Não abra banco de uma versão mais recente com código antigo: o bloqueio explícito é intencional.
- Os testes de HTML cobrem cenários simulados, não todas as configurações e dados reais do portal.
