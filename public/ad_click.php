<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/ads.php';
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if(!$id || !installed()){http_response_code(404);exit('Publicidade indisponível.');}
try {
  $db=db();
  if(setting('ads_enabled','0')!=='1'){http_response_code(404);exit('Publicidade indisponível.');}
  $s=$db->prepare("SELECT c.target_url,c.id FROM ad_campaigns c JOIN ad_placements p ON c.placement_slug=p.slug WHERE c.id=? AND c.active=1 AND c.start_date<=CURDATE() AND (c.end_date IS NULL OR c.end_date>=CURDATE()) AND p.enabled=1 AND p.provider='local' LIMIT 1");
  $s->execute([$id]);$row=$s->fetch();
  if(!$row){http_response_code(404);exit('Anúncio indisponível.');}
  $target=ad_validate_url((string)$row['target_url']);
  $db->prepare('UPDATE ad_campaigns SET clicks=clicks+1 WHERE id=?')->execute([$row['id']]);
  header('Location: '.$target,true,302);exit;
} catch(Throwable) {http_response_code(404);exit('Publicidade indisponível.');}
