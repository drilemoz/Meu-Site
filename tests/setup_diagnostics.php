<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/requirements.php';
function assert_setup(bool $good, string $label): void { if (!$good) throw new RuntimeException('Falhou: '.$label); echo "OK: $label\n"; }
$rows=igua_requirements();
assert_setup(count($rows)>=10,'requisitos identificados');
assert_setup(count(array_filter($rows,static fn($r)=>$r['required']))>=8,'obrigatórios identificados');
assert_setup(!igua_required_ready([['required'=>true,'ok'=>false]]),'bloqueia instalação sem requisito');
assert_setup(igua_required_ready([['required'=>false,'ok'=>false],['required'=>true,'ok'=>true]]),'opcionais não bloqueiam instalação');
$messages=[
    [2002,'Connection refused','não está respondendo'],
    [1045,'Access denied for user','senha rejeitados'],
    [1044,'Access denied for database','sem acesso'],
    [1049,'Unknown database','inexistente'],
    [2005,'Unknown MySQL server host','não encontrado'],
];
foreach ($messages as [$code,$reason,$expected]) {
    $e=new PDOException('SQLSTATE[HY000] ['.$code.'] '.$reason);
    $result=igua_db_diagnosis($e,'127.0.0.1',3306,'iguanews');
    assert_setup(str_contains($result[0],$expected),'diagnóstico MySQL '.$code);
}
$r=igua_db_diagnosis(new PDOException('SQLSTATE[HY000] [2002] Connection refused'), '127.0.0.1', 3306, 'iguanews');
assert_setup(str_contains(implode(' ',$r),'hospedagem') || str_contains(implode(' ',$r),'mariadb-admin'),'diagnóstico adequado ao PHP em execução');
assert_setup(!str_contains(implode(' ',$r),'SENHA_REAL'),'não imprime senhas');
echo "Testes isolados do instalador aprovados.\n";
