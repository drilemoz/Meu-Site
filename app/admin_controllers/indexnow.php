<?php
declare(strict_types=1);
require_ability('manage_settings',$me);
if(($me['role']??'')!=='admin')throw new RuntimeException('Apenas administradores podem configurar o IndexNow.');
require_once __DIR__.'/../indexnow.php';
$site=setting('seo_site_url','');
if(setting('seo_index','0')!=='1')throw new RuntimeException('Ative a indexação em Configurações e SEO antes de enviar URLs.');
if($action==='indexnow_setup'){
    igua_indexnow_setup($site,IGUA_PUBLIC,IGUA_STORAGE);
    igua_security_event('settings_changed','indexnow_configured');
    flash('IndexNow preparado. Abra o arquivo de verificação para confirmar que ele responde publicamente, antes de enviar URLs.');
}elseif($action==='indexnow_submit'){
    $cfg=igua_indexnow_read(IGUA_STORAGE);
    if($cfg===null)throw new RuntimeException('Configure primeiro o IndexNow.');
    if($cfg['site']!==igua_sitemap_base($site))throw new RuntimeException('O domínio oficial mudou desde a configuração do IndexNow.');
    $previous=(int)($_SESSION['igua_indexnow_attempt']??0);
    if(time()-$previous<60)throw new RuntimeException('Aguarde um minuto antes de outro envio IndexNow.');
    $raw=$_POST['indexnow_path']??'';
    if(!is_string($raw))throw new InvalidArgumentException('Caminho inválido.');
    $catalog=igua_sitemap_catalog($db,$site)['items'];
    $urls=igua_indexnow_select_urls($catalog,$site,$raw,10);
    if(!$urls)throw new RuntimeException('Não há notícias ou páginas legais publicadas para enviar.');
    $_SESSION['igua_indexnow_attempt']=time();
    $result=igua_indexnow_submit($cfg,$urls,IGUA_PUBLIC);
    $_SESSION['igua_indexnow_last']=['count'=>$result['count'],'status'=>$result['status'],'time'=>time()];
    igua_security_event('admin_action','indexnow_manual_submit');
    flash('IndexNow recebeu '.(int)$result['count'].' URL(s) (HTTP '.(int)$result['status'].'). Recebimento não garante indexação.');
}else throw new InvalidArgumentException('Ação IndexNow inválida.');
redirect('/admin.php?action=sitemap#indexnow');
