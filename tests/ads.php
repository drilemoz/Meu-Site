<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/ads.php';
require __DIR__.'/../app/ads_examples.php';
require __DIR__.'/../app/backup.php';
function ad_ok(bool $yes,string $label): void {if(!$yes)throw new RuntimeException('Falhou: '.$label);echo "OK: {$label}\n";}
ad_ok(count(AD_SLOTS)===6 && array_keys(AD_SLOTS)===['p01','p02','p03','p04','p05','p06'],'seis posições estáveis');
ad_ok(ad_valid_slot('p06') && !ad_valid_slot('p07'),'posições inválidas bloqueadas');
ad_ok(ad_validate_publisher('ca-pub-1234567890123456')==='ca-pub-1234567890123456','ID AdSense válido');
foreach(['ca-pub-xx','ca-pub-123456','<script>alert(1)</script>'] as $bad){try{ad_validate_publisher($bad);throw new RuntimeException('Aceitou identificação incorreta.');}catch(RuntimeException $e){if(str_starts_with($e->getMessage(),'Aceitou'))throw $e;}}
ad_ok(true,'ID AdSense inseguro rejeitado');
foreach(['javascript:alert(1)','http://example.com','https://x.org@unsafe.net',"https://example.com/path\r\nHost:unsafe",'ftp://site.com'] as $bad){try{ad_validate_url($bad);throw new RuntimeException('Aceitou URL insegura');}catch(RuntimeException $e){if(str_starts_with($e->getMessage(),'Aceitou'))throw $e;}}
ad_ok(ad_validate_url('https://lojax.example/oferta?a=1')==='https://lojax.example/oferta?a=1','endereço de publicidade HTTPS');
ad_ok(ad_validate_day('2026-10-18',true)==='2026-10-18' && ad_validate_day('',false)===null,'datas opcional/obrigatória');
foreach(['2026-02-30','2026-13-01','30/10/2026'] as $bad){try{ad_validate_day($bad,true);throw new RuntimeException('Aceitou data inválida');}catch(RuntimeException $e){if(str_starts_with($e->getMessage(),'Aceitou'))throw $e;}}
ad_ok(true,'datas impossíveis rejeitadas');
$v5=[];foreach(BACKUP_TABLES_V5 as $table)$v5[$table]=[];
$v5['users']=[['id'=>1,'name'=>'Admin','email'=>'admin@example.test','role'=>'admin','active'=>1]];
$p=parse_backup(json_encode(['format'=>'iguanews-backup','version'=>5,'tables'=>$v5],JSON_THROW_ON_ERROR),'old.json');
ad_ok(count($p['tables']['ad_campaigns'])===0 && count($p['tables']['ad_placements'])===0,'backup v5 convertido para v6');
$v6=array_replace(array_fill_keys(BACKUP_TABLES_V6,[]),$v5);
$v6['ad_placements']=[['slug'=>'p01','provider'=>'off','enabled'=>0,'adsense_slot'=>'']];
$v6['ad_campaigns']=[['id'=>1,'placement_slug'=>'p01','title'=>'Loja local','advertiser'=>'Comércio','target_url'=>'https://example.test','active'=>0]];
$p=parse_backup(json_encode(['format'=>'iguanews-backup','version'=>6,'tables'=>$v6],JSON_THROW_ON_ERROR),'new.json');
ad_ok(count($p['tables']['ad_placements'])===1 && count($p['tables']['ad_campaigns'])===1,'backup v6 inclui campanhas e posições');
$legacy=array_fill_keys(BACKUP_TABLES_V1,[]);$legacy['users']=$v5['users'];
$p=parse_backup(json_encode(['format'=>'iguanews-backup','version'=>1,'tables'=>$legacy],JSON_THROW_ON_ERROR),'v1.json');
ad_ok(count($p['tables']['ad_campaigns'])===0,'backup v1 antigo preservado');
$index=file_get_contents(__DIR__.'/../public/index.php');$admin=file_get_contents(__DIR__.'/../public/admin.php');
foreach(['p01','p02','p03','p04','p05','p06'] as $code){$whole=$index.file_get_contents(__DIR__.'/../app/bus_public.php').file_get_contents(__DIR__.'/../app/ads.php');ad_ok(str_contains($whole,"'{$code}'"),'slot conectado: '.$code);}
ad_ok(str_contains($admin,'manage_settings') && str_contains($admin,'ads_save_slot'),'admin controla publicidade com permissão');
$js=file_get_contents(__DIR__.'/../public/assets/js/main.js');
ad_ok(str_contains($js,'adConsentAllow') && str_contains($js,'adConsentDeny') && str_contains($js,'document.head.appendChild(script)'),'nenhuma biblioteca Google carregada pelo PHP antes de consentimento');
echo "Validação isolada do módulo de publicidade concluída. Requer MariaDB e navegador para validação integrada.\n";

$examples=ad_demo_campaigns();
ad_ok(count($examples)===6,'seis campanhas de exemplo criadas');
$slots=array_map(static fn($item)=>(string)$item['placement_slug'],$examples);sort($slots);
ad_ok($slots===['p01','p02','p03','p04','p05','p06'],'exemplos cobrem todas as posições');
ad_ok(ad_is_demo_campaign(['target_url'=>AD_DEMO_URL_PREFIX.'p01-topo-home']) && !ad_is_demo_campaign(['target_url'=>'https://example.com/anuncio-real']),'marcação de campanhas de exemplo');
