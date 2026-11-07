<?php
declare(strict_types=1);
require_once __DIR__.'/../app/auth_remember.php';
require_once __DIR__.'/../app/schema_migrations.php';
function assert140(bool $ok,string $why):void {if(!$ok)throw new RuntimeException('Falha login 140: '.$why);}
class MockRememberStmt140 extends PDOStatement {
    private array $rows=[];private int $changed=0;
    public function __construct(private MockRememberPDO140 $db,private string $sql){}
    public function execute(?array $params=null):bool {
        $args=$params??[];$sql=$this->sql;$this->changed=0;
        if(str_starts_with($sql,'INSERT INTO admin_remember_tokens')) {
            [$selector,$uid,$hash,$cred,$expires]=$args;
            $this->db->tokens[$selector]=['selector'=>$selector,'user_id'=>$uid,'validator_hash'=>$hash,'credential_hash'=>$cred,'expires_at'=>$expires];$this->changed=1;
        }elseif(str_contains($sql,'FROM admin_remember_tokens t INNER JOIN users u')) {
            $row=$this->db->tokens[$args[0]]??null;
            $this->rows=$row && strtotime($row['expires_at'])>time() ? [array_merge($row,$this->db->users[$row['user_id']])] : [];
        }elseif(str_starts_with($sql,'SELECT selector FROM')) {
            $this->rows=array_keys(array_filter($this->db->tokens,fn($v)=>$v['user_id']===$args[0]));
        }elseif(str_starts_with($sql,'UPDATE admin_remember_tokens')) {
            [$hash,$selector,$expected]=$args;
            if(isset($this->db->tokens[$selector]) && $this->db->tokens[$selector]['validator_hash']===$expected) {
                $this->db->tokens[$selector]['validator_hash']=$hash;$this->changed=1;
            }
        }elseif(str_starts_with($sql,'DELETE FROM admin_remember_tokens WHERE selector=?')) {
            $selector=$args[0];if(isset($this->db->tokens[$selector])) {unset($this->db->tokens[$selector]);$this->changed=1;}
        }elseif(str_starts_with($sql,'DELETE FROM admin_remember_tokens WHERE user_id=?')) {
            foreach($this->db->tokens as $selector=>$row)if($row['user_id']===$args[0]){unset($this->db->tokens[$selector]);$this->changed++;}
        }elseif(str_starts_with($sql,'DELETE FROM admin_remember_tokens WHERE expires_at')) {
            foreach($this->db->tokens as $selector=>$row)if(strtotime($row['expires_at'])<=time())unset($this->db->tokens[$selector]);
        }
        return true;
    }
    public function fetch(int $mode=PDO::FETCH_DEFAULT,int $cursorOrientation=PDO::FETCH_ORI_NEXT,int $cursorOffset=0):mixed{return $this->rows[0]??false;}
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,mixed ...$args):array{return $this->rows;}
    public function rowCount():int {return $this->changed;}
}
class MockRememberPDO140 extends PDO {
    public array $tokens=[];public array $users=[];public array $statements=[];
    public function __construct(){}
    public function prepare(string $query,array $options=[]):PDOStatement|false{return new MockRememberStmt140($this,$query);}
    public function exec(string $statement):int|false{$this->statements[]=$statement;return 0;}
}
$db=new MockRememberPDO140();
$passHash=password_hash('senha-de-teste-segura',PASSWORD_DEFAULT);
$user=['id'=>7,'name'=>'Teste','email'=>'admin@example.invalid','role'=>'admin','active'=>1,'password_hash'=>$passHash];
$db->users[7]=$user;
$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['SERVER_NAME']='127.0.0.1';unset($_SERVER['HTTPS']);
$_SESSION=[];
assert140(igua_remember_local_loopback('127.0.0.1','127.0.0.1','cli-server'),'Termux local autoriza opt-in');
assert140(!igua_remember_local_loopback('127.0.0.1','127.0.0.1','fpm-fcgi'),'proxy interno HTTP não pode autorizar cookie inseguro');
$_SERVER['HTTPS']='on';
assert140(igua_remember_issue($db,$user),'grava token válido em HTTPS');
unset($_SERVER['HTTPS']);
assert140(count($db->tokens)===1,'somente hash no banco');
$selector=array_key_first($db->tokens);$row=$db->tokens[$selector];
assert140(!str_contains($row['validator_hash'],':')&&strlen($row['validator_hash'])===64,'validador não armazenado em texto claro');
assert140(!igua_remember_parse('nao-aceito') && !igua_remember_parse(str_repeat('f',32).':'.str_repeat('A',64)),'formato rigoroso');
assert140(igua_remember_parse($selector.':'.str_repeat('a',64))!==null,'token hexadecimal esperado');
assert140(igua_remember_cookie_options(time())['httponly']===true && igua_remember_cookie_options(time())['samesite']==='Lax','proteção dos cookies');
$_SERVER['REMOTE_ADDR']='192.0.2.10';$_SERVER['SERVER_NAME']='site.example';
assert140(!igua_remember_is_secure_transport(),'HTTP remoto bloqueado');
assert140(!igua_remember_issue($db,$user) && count($db->tokens)===1,'HTTP remoto não emite credenciais');
$_SERVER['HTTPS']='on';
assert140(igua_remember_is_secure_transport() && igua_remember_cookie_options(time())['secure'],'HTTPS remoto permite cookie seguro');
$_SERVER['HTTPS']='on';$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['SERVER_NAME']='127.0.0.1';
// Exercita token verdadeiro reconstituindo somente o hash para a simulação de revogação:
// O valor em texto puro não pode ser recuperado do banco.
$token=bin2hex(random_bytes(32));$row['validator_hash']=hash('sha256',$token);$db->tokens[$selector]=$row;
$_COOKIE[IGUA_REMEMBER_COOKIE]=$selector.':'.$token;
if(session_status()===PHP_SESSION_NONE)session_start();
assert140(igua_remember_auto_login($db),'reautenticação válida');
assert140((int)$_SESSION['admin']['id']===7 && !isset($_SESSION['admin']['password_hash']),'sessão sem hash de senha');
assert140($db->tokens[$selector]['validator_hash']!==hash('sha256',$token),'token gira após reautenticação');
$_SESSION=[];
$_COOKIE[IGUA_REMEMBER_COOKIE]=$selector.':'.$token;
assert140(!igua_remember_auto_login($db) && !isset($db->tokens[$selector]),'reutilização de token antigo é rejeitada');
// Token novo vinculado ao hash antigo deve ser revogado após troca de senha.
$second=bin2hex(random_bytes(32));$db->tokens[$selector]=array_merge($row,['validator_hash'=>hash('sha256',$second)]);
$_COOKIE[IGUA_REMEMBER_COOKIE]=$selector.':'.$second;
$db->users[7]['password_hash']=password_hash('senha-nova',PASSWORD_DEFAULT);
assert140(!igua_remember_auto_login($db) && !isset($db->tokens[$selector]),'troca de senha invalida token');
$db->users[7]['password_hash']=$passHash;
$db->tokens[$selector]=$row;
$db->tokens[$selector]['expires_at']=date('Y-m-d H:i:s',time()-3600);
$_COOKIE[IGUA_REMEMBER_COOKIE]=$selector.':'.$second;
assert140(!igua_remember_auto_login($db) && !isset($db->tokens[$selector]),'token expirado não autentica');
$db->tokens[$selector]=$row;
$db->tokens[$selector]['validator_hash']=hash('sha256',$second);
$db->users[7]['active']=0;
$_COOKIE[IGUA_REMEMBER_COOKIE]=$selector.':'.$second;
assert140(!igua_remember_auto_login($db) && !isset($db->tokens[$selector]),'usuário desativado não autentica');
$db->users[7]['active']=1;
$db->tokens[$selector]=$row;
igua_remember_revoke_user($db,7);
assert140(!$db->tokens,'revogação de todos os dispositivos');
$db->tokens[$selector]=$row;
$_COOKIE[IGUA_REMEMBER_COOKIE]=$selector.':'.$second;
igua_remember_logout($db);
assert140(!isset($db->tokens[$selector]) && !isset($_COOKIE[IGUA_REMEMBER_COOKIE]),'logout remove token do banco e do dispositivo');
igua_remember_schema($db);
assert140(count($db->statements)===1 && str_contains($db->statements[0],'FOREIGN KEY'),'tabela de autenticação integrada ao banco');
echo "1.0.40: token, rotação, revogação, credencial e cookies validados com PDO simulado.\n";
