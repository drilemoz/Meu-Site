<?php
declare(strict_types=1);
require_once __DIR__.'/../app/dns_diagnostic.php';
$count=0;
function ok236(bool $condition,string $description):void {global $count;$count++;if(!$condition)throw new RuntimeException('DNS 236: '.$description);}

ok236(igua_dns_site_hosts('https://iguatama.online/')===['iguatama.online','www.iguatama.online'],'site raiz + www');
ok236(igua_dns_site_hosts('https://www.iguatama.online')===['www.iguatama.online','iguatama.online'],'www + raiz');
foreach(['http://iguatama.online','https://localhost/','https://192.168.1.2','https://foo.local','https://x.test/',
         'https://user:pass@iguatama.online','https://iguatama.online/?next=1','https://iguatama.online:8080',
         'https://a..online','https://ex-ample.invalid','https://example.com/site'] as $bad){
    ok236(igua_dns_site_hosts($bad)===[],'rejeição de URL/domínio inseguro: '.$bad);
}
ok236(igua_dns_normalize_value('A','8.8.8.8')==='8.8.8.8','IPv4');
ok236(igua_dns_normalize_value('AAAA','2001:db8::2')==='2001:db8::2','IPv6');
ok236(igua_dns_normalize_value('A','<script>')===null,'IP malicioso bloqueado');
ok236(igua_dns_normalize_value('NS','Ns1.Example.Org.')==='ns1.example.org','NS normalizado');
$localFake=static function(string $host,int $flag):array|false {
    if($flag===DNS_A)return [['type'=>'A','ip'=>'203.0.113.1'],['type'=>'A','ip'=>'203.0.113.1'],['type'=>'TXT','txt'=>'secreto']];
    if($flag===DNS_NS)return [['type'=>'NS','target'=>'ns1.example.org.']];
    if($flag===DNS_AAAA)return [];
    return false;
};
$local=igua_dns_local_records('iguatama.online',$localFake);
ok236($local['A']['values']===['203.0.113.1'],'IPv4 local sem duplicatas');
ok236($local['AAAA']['state']==='empty','ausência não é erro');
ok236($local['CNAME']['state']==='error','erro de DNS não é ausência');
ok236($local['NS']['values']===['ns1.example.org'],'NS local');
$answer=json_encode(['Status'=>0,'Answer'=>[['name'=>'iguatama.online.','type'=>1,'data'=>'203.0.113.1'],['type'=>16,'data'=>'segredo'],['type'=>1,'data'=>'<script>']]]);
ok236(igua_dns_doh_decode($answer,'iguatama.online','A')['values']===['203.0.113.1'],'DoH somente tipo correto e dado válido');
ok236(igua_dns_doh_decode('{invalid','iguatama.online','A')['state']==='error','JSON inválido');
ok236(igua_dns_doh_decode(json_encode(['Status'=>2]),'iguatama.online','A')['state']==='error','erro do resolvedor');
ok236(igua_dns_doh_decode(json_encode(['Status'=>3]),'iguatama.online','A')['state']==='empty','NXDOMAIN distinto de indisponibilidade');
$publicFake=static function():array{return ['Google'=>[
    'A'=>['state'=>'ok','values'=>['203.0.113.1']], 'AAAA'=>['state'=>'empty','values'=>[]],
    'CNAME'=>['state'=>'error','values'=>[]], 'NS'=>['state'=>'ok','values'=>['ns2.example.org']]],
    'Cloudflare'=>['A'=>['state'=>'ok','values'=>['203.0.113.2']], 'AAAA'=>['state'=>'empty','values'=>[]],
    'CNAME'=>['state'=>'error','values'=>[]], 'NS'=>['state'=>'ok','values'=>['ns1.example.org']]]];};
$snap=igua_dns_snapshot('https://iguatama.online',$localFake,$publicFake);
ok236($snap['ok']===true && count($snap['hosts'])===2,'snapshot automático de domínio configurado');
ok236($snap['comparison']['A']==='different','divergência entre resolvedores');
ok236($snap['comparison']['AAAA']==='same','sem AAAA em todos');
ok236($snap['comparison']['CNAME']==='unknown','erros não tratados como não há registro');
ok236(igua_dns_snapshot('',$localFake,$publicFake)['ok']===false,'domínio ausente não consulta rede');
$root=dirname(__DIR__);
$admin=file_get_contents($root.'/public/admin.php');
ok236(str_contains($admin,"'dns_diagnostic'=>'manage_settings'") && str_contains($admin,"'server_info','dns_diagnostic','files'"),'rota protegida e permitida');
$layout=file_get_contents($root.'/app/admin_views/layout.php');
ok236(str_contains($layout,'Diagnóstico de DNS') && str_contains($layout,"$"."toolsActions=['files','server_info','dns_diagnostic']"),'menu Ferramentas');
function h(mixed $val):string{return htmlspecialchars((string)$val,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function admin_link(string $action):string{return '/admin.php?action='.$action;}
function setting(string $key,string $default=''):string{return 'https://iguatama.online';}
// Captura sem consultas DNS externas, injetando snapshot pré-calculado na rotina antes da view (teste de template).
$view=file_get_contents($root.'/app/admin_views/routes/dns_diagnostic.php');
ok236(str_contains($view,'igua_dns_snapshot((string)setting(\'seo_site_url\',\'\'))') && str_contains($view,'Atualizar') && str_contains($view,'Cloudflare'),'template automático e refresh');
ok236(!str_contains($view,'dns_get_record($_GET')&&!str_contains($view,'shell_exec'),'sem hostname arbitrário no painel');
$css=file_get_contents($root.'/public/assets/css/modules/20-dns-diagnostic.css');
ok236(str_contains($css,'@media(max-width:740px)'),'responsivo');
echo "OK dns_diagnostic_236: {$count} verificações.\n";
