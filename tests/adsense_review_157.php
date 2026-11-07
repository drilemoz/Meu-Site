<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/adsense_review.php';
require __DIR__.'/../app/updater.php';
function check157(bool $ok,string $name):void {
    if(!$ok) throw new RuntimeException('Falhou: '.$name);
    echo "OK $name\n";
}
$pub='ca-pub-1234567890123456';
check157(igua_adsense_verification_meta($pub)==='<meta name="google-adsense-account" content="'.$pub.'">','metatag oficial sem script de anúncios');
foreach(['', 'ca-pub-1234', '<script>alert(1)</script>',"\"/><script>"] as $bad) {
    check157(igua_adsense_verification_meta($bad)==='', 'metatag rejeita valor inválido');
}
check157(igua_adsense_ads_txt_line($pub)==='google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0','linha ads.txt com editor verdadeiro');
$initial="# comentário\nexample.com, pub-example, DIRECT\n";
$one=igua_adsense_ads_txt_build($initial,$pub);
check157(str_contains($one,'example.com, pub-example, DIRECT'),'não apaga outras redes');
check157(substr_count($one,'google.com, pub-1234567890123456')===1,'uma entrada Google válida');
check157(igua_adsense_ads_txt_build($one,$pub)===$one,'repetir é idempotente');
check157(igua_adsense_ads_txt_has_publisher($one,$pub),'verifica ID exato');
check157(!igua_adsense_ads_txt_has_publisher('# '.igua_adsense_ads_txt_line($pub)."\n",$pub),'comentário não equivale a autorização');
try{igua_adsense_ads_txt_build("google.com, pub-9999999999999999, DIRECT\n",$pub);throw new RuntimeException('Outra conta foi substituída');}catch(RuntimeException $e){check157(str_contains($e->getMessage(),'diferente'),'impede substituir outro editor');}
$folder=sys_get_temp_dir().'/adsense-check-'.bin2hex(random_bytes(6));mkdir($folder,0700);
try {
    file_put_contents($folder.'/ads.txt',$initial);
    igua_adsense_ads_txt_write($pub,$folder);
    $saved=file_get_contents($folder.'/ads.txt');
    check157($saved===$one,'gravação atômica preserva entradas existentes');
} finally { @unlink($folder.'/ads.txt');@rmdir($folder); }
check157(!igua_updater_safe_path('public/ads.txt'),'atualizador não toca arquivo do editor');
check157(is_file(__DIR__.'/../app/public_views/routes/about.php'),'página Sobre presente');
$pubRoute=file_get_contents(__DIR__.'/../public/index.php');
check157(str_contains($pubRoute,"'contato','sobre'"),'rota Sobre pública');
$layout=file_get_contents(__DIR__.'/../app/public_views/layout.php');
check157(str_contains($layout,'igua_adsense_verification_meta'),'verificação em head público');
$admin=file_get_contents(__DIR__.'/../app/ads_admin.php');
check157(str_contains($admin,'adsense-review'),'vistoria no painel');
echo "Pré-avaliação AdSense: testes locais concluídos; revisão real cabe ao Google.\n";
