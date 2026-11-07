<?php
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/integrations.php';
function admin_link(string $action):string{return '/admin.php?action='.$action;}
$cfg=['ga4_enabled'=>'1','ga4_measurement_id'=>'G-ABCD123456','adsense_publisher'=>'ca-pub-1234567890123456','ads_enabled'=>'0','google_site_verification'=>'','seo_site_url'=>'https://exemplo.com.br','seo_index'=>'1'];
$rows=igua_integrations_diagnostic($cfg,IGUA_PUBLIC,'');
ob_start();require __DIR__.'/../app/admin_views/routes/integrations.php';$html=ob_get_clean();
$css=file_get_contents(__DIR__.'/../public/assets/css/style.css');
$js=file_get_contents(__DIR__.'/../public/assets/js/integrations-admin.js');
echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>'.$css.'</style></head><body class="admin-body"><main class="admin-main" style="padding:14px 12px"><div class="admin-top"><div><small>PORTAL / ADMINISTRAÇÃO</small><h1>Integrações e serviços</h1></div></div>'.$html.'</main><script>'.$js.'</script></body></html>';
