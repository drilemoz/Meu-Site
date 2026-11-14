<?php
declare(strict_types=1);
/** Contrato de segurança do teste de provisionamento CI. Não conecta a bancos. */
$root=dirname(__DIR__);
$workflow=(string)file_get_contents($root.'/.github/workflows/integration-mariadb.yml');
$test=(string)file_get_contents($root.'/tests/integration/installer_create_mariadb_231.php');
function check235(bool $condition, string $description): void {
    if (!$condition) throw new RuntimeException('CI MySQL local: '.$description);
}
check235(str_contains($workflow,'IGUA_TEST_MARIADB_CONTAINER: ${{ job.services.mariadb.id }}'),'identificação do serviço real pelo contexto GitHub Actions');
check235(str_contains($workflow,"docker inspect --format '{{.State.Pid}}'"),'PID do contêiner do serviço inspecionado');
check235(str_contains($workflow,'nsenter --target "$service_pid" --net -- php tests/integration/installer_create_mariadb_231.php'),'teste executado no namespace de rede do serviço');
check235(str_contains($workflow,'set -euo pipefail') && str_contains($workflow,'ID do contêiner MariaDB inválido'),'falha fechada diante de namespace indisponível');
check235(str_contains($test,'$mysqlIdentity=(string)$admin->query(\'SELECT USER()\')->fetchColumn();'),'servidor verifica origem efetiva do cliente');
check235(str_contains($test,"['localhost','127.0.0.1','::1']"),'somente conexões locais no teste real');
check235(str_contains($test,"['localhost','127.0.0.1']") && str_contains($test,'$admin->exec(\'DROP USER IF EXISTS \''),'teste remove usuários próprios nos dois hosts');
check235(str_contains($workflow,'php tests/integration/updater_mariadb_217.php'),'teste de atualizador posterior preservado');
check235(!str_contains($workflow,"GRANT ALL ON *.* TO") && !str_contains($workflow,"MARIADB_ROOT_HOST: localhost"),'CI não contorna o teste por ampliação de grants');
echo "OK: CI de provisionamento usa loopback real do MariaDB, sem liberar usuários externos (9 contratos).\n";
