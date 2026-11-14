<?php
declare(strict_types=1);
require_once __DIR__.'/support/integrations_source.php';
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/integrations.php';
function dns172(bool $cond,string $message):void {if(!$cond)throw new RuntimeException('Search DNS 172: '.$message);echo 'OK: '.$message.PHP_EOL;}
$txt='google-site-verification=AbcDeF123_abc-xyz';
$cfg=['seo_site_url'=>'https://iguatama.online','google_dns_verification_txt'=>$txt];
dns172(igua_search_dns_domain($cfg)==='iguatama.online','resolve hostname from configured HTTPS canonical URL');
dns172(igua_search_dns_domain(['seo_site_url'=>'https://www.example.com/a/'])==='www.example.com','preserve explicit hostname without guessing domain owner');
foreach(['http://foo.example','https://localhost','https://127.0.0.1','https://wrong.invalid?token=a','https://user:pass@example.com'] as $bad){dns172(igua_search_dns_domain(['seo_site_url'=>$bad])==='','reject unsafe or not configured domain');}
dns172(igua_search_dns_expected($txt)===$txt,'accept full Google DNS TXT token');
dns172(igua_search_dns_expected('')==='','allow explicit removal of expected TXT');
foreach(['abc','example.com','<script>','google-site-verification=x','google-site-verification=abc\nInjected','google-site-verification='.str_repeat('a',310),[]] as $bad){
    $failed=false;try{igua_search_dns_expected((string)(is_array($bad)?'bad':$bad));}catch(InvalidArgumentException){$failed=true;}
    dns172($failed,'reject incorrect or injected expected DNS tokens');
}
$records=[['type'=>'TXT','txt'=>'v=spf1 include:_spf.example.com -all'],['type'=>'TXT','entries'=>['google-site-verification=AbcDeF','123_abc-xyz']]];
$matched=igua_search_dns_check($cfg,static fn(string $domain)=>$records);
dns172($matched['status']==='matched' && $matched['count']===1 && $matched['domain']==='iguatama.online','exact expected TXT found, unrelated TXT ignored');
$found=igua_search_dns_check(array_replace($cfg,['google_dns_verification_txt'=>'']),static fn()=>[['txt'=>$txt]]);
dns172($found['status']==='found'&&$found['count']===1,'detect Google TXT without conflating it with account ownership');
$mismatch=igua_search_dns_check(array_replace($cfg,['google_dns_verification_txt'=>'google-site-verification=Other123456789']),static fn()=>[['txt'=>$txt]]);
dns172($mismatch['status']==='mismatch','report different Google token instead of verified');
$empty=igua_search_dns_check($cfg,static fn()=>[]);
dns172($empty['status']==='mismatch' && $empty['count']===0,'expected token not found');
$missing=igua_search_dns_check(array_replace($cfg,['google_dns_verification_txt'=>'']),static fn()=>[]);
dns172($missing['status']==='missing','absence of token is not a confirmed Google failure');
$error=igua_search_dns_check($cfg,static fn()=>false);
dns172($error['status']==='unavailable','failed DNS resolver not reported as no record');
$noDomain=igua_search_dns_check(['seo_site_url'=>'','google_dns_verification_txt'=>$txt],static fn()=>throw new RuntimeException('must not call'));
dns172($noDomain['status']==='no_domain','unconfigured canonical URL prevents external DNS query');
$session=['igua_search_dns_last_check'=>$matched+['expected_hash'=>hash('sha256',$txt)]];
dns172(igua_search_dns_last_check($cfg,$session)['status']==='matched','recent result kept only for exact domain and expected token');
dns172(igua_search_dns_last_check(array_replace($cfg,['seo_site_url'=>'https://other.example.com']),$session)===null,'result discarded when domain changes');
dns172(igua_search_dns_last_check(array_replace($cfg,['google_dns_verification_txt'=>'']),$session)===null,'result discarded when expected token changes');
$old=$session;$old['igua_search_dns_last_check']['checked_at']=time()-901;
dns172(igua_search_dns_last_check($cfg,$old)===null,'expire cached DNS result');
class DnsTestPDO172 extends PDO{
 public array $writes=[];public function __construct(){}public function beginTransaction():bool{return true;}public function commit():bool{return true;}public function rollBack():bool{return true;}public function inTransaction():bool{return false;}
 public function prepare(string $query,array $options=[]):PDOStatement|false {return new DnsTestStmt172($this);}
}
class DnsTestStmt172 extends PDOStatement {public function __construct(private DnsTestPDO172 $db){}public function execute(?array $params=null):bool{$this->db->writes[$params[0]]=$params[1];return true;}}
$db=new DnsTestPDO172();igua_integrations_save($db,'integrations_save_dns',['google_dns_verification_txt'=>$txt]);
dns172(($db->writes['google_dns_verification_txt']??'')===$txt,'store expected DNS token in existing settings table without migration');
$invalid=false;try{igua_integrations_save($db,'integrations_save_dns',['google_dns_verification_txt'=>['invalid']]);}catch(InvalidArgumentException){$invalid=true;}
dns172($invalid,'reject array field in DNS settings');
$root=dirname(__DIR__);
$routes=file_get_contents($root.'/public/admin.php');
$postRoutes=file_get_contents($root.'/app/admin_controllers/post_routes.php');
$view=igua_test_integrations_source();
dns172(str_contains($routes,"'integrations_check_dns'=>'manage_settings'")&&str_contains($postRoutes,"'integrations_check_dns'=>'integrations'")&&str_contains($routes,'check_csrf();'),'DNS check restricted to admin with CSRF and explicit POST route');
dns172(str_contains($view,'DNS (TXT)')&&str_contains($view,'TXT esperado')&&str_contains($view,'data-dns-test-form')&&str_contains($view,'iguatama.online')===false,'mobile UI includes DNS test without hardcoded client domain');
dns172(substr_count($view,'<details')===substr_count($view,'</details>'),'new and old collapsible HTML sections balanced');
echo 'Search Console DNS 1.0.72: testes locais concluídos; confirmação Google e DNS público exigem hospedagem real.'.PHP_EOL;
