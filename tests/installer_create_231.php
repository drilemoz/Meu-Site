<?php
declare(strict_types=1);
require_once __DIR__.'/../app/installer.php';
$checks=0;
function check231(bool $ok, string $description): void {global $checks;$checks++;if(!$ok)throw new RuntimeException('Falhou: '.$description);}
function reject231(callable $fn, string $description):void {
    global $checks;$checks++;
    try {$fn();}catch(InvalidArgumentException $e){return;}
    throw new RuntimeException('Não rejeitou: '.$description);
}
class InstallerCreateRecorder231 extends PDO {
    public array $sql=[];
    public bool $duplicate=false;
    public function __construct() {}
    public function exec(string $statement): int|false {
        $this->sql[]=$statement;
        if($this->duplicate){$e=new PDOException('Banco existente');$e->errorInfo=['HY000',1007,'Banco existente'];throw $e;}
        return 1;
    }
}
$pdo=new InstallerCreateRecorder231();
igua_create_database_with_login($pdo,'novo_portal_231');
check231($pdo->sql===['CREATE DATABASE `novo_portal_231` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'], 'cria apenas banco novo, sem IF NOT EXISTS nem DROP');
check231(!str_contains(implode(' ',$pdo->sql),'GRANT') && !str_contains(implode(' ',$pdo->sql),'CREATE USER'), 'não concede privilégios globais nem cria usuários');
foreach(['xx;DROP','3banco','../nome','banco`nome','banco espaço',str_repeat('a',65)] as $bad){
    reject231(static fn()=>igua_create_database_with_login(new InstallerCreateRecorder231(),$bad),'identificador inválido '.$bad);
}
$duplicate=new InstallerCreateRecorder231();$duplicate->duplicate=true;
$rejected=false;
try {igua_create_database_with_login($duplicate,'portal_existente');}catch(PDOException){$rejected=true;}
check231($rejected && count($duplicate->sql)===1, 'não reutiliza nem apaga banco existente');
$hosted=new InstallerCreateRecorder231();
reject231(static fn()=>igua_provision_hosting_local($hosted,'site','db.remoto.com',3306), 'usuário dedicado remoto bloqueado');
reject231(static fn()=>igua_provision_hosting_local($hosted,'site','%',3306), 'host wildcard bloqueado');
reject231(static fn()=>igua_provision_hosting_local($hosted,'site','localhost',0), 'porta inválida bloqueada');
check231($hosted->sql===[],'rejeição não executa SQL');
// Por construção, a etapa de criação deve estar no instalador e ser protegida por HTTPS na hospedagem.
$setup=(string)file_get_contents(__DIR__.'/../public/setup.php');
foreach(['create_db','provision_db','igua_create_database_with_login','igua_provision_hosting_local',"\$runtime['https']",'Criar banco automaticamente','Criar banco e usuário exclusivo','Conectar a banco existente'] as $symbol){
    check231(str_contains($setup,$symbol),'rota e interface: '.$symbol);
}
check231(str_contains($setup,'$databaseCreated'), 'informa quando banco novo foi criado mas etapa seguinte falhou');
check231(!str_contains($setup, "\$_SESSION['admin_password_db']"), 'senha administrativa não persistida em sessão');
echo "OK installer_create_231: {$checks} verificações.\n";
