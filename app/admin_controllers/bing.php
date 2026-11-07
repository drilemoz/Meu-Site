<?php
declare(strict_types=1);
/** Configuração e consulta somente por administrador, após validação CSRF central. */
require_ability('manage_settings',$me);
if(($me['role']??'')!=='admin')throw new RuntimeException('Somente administradores podem consultar o Bing.');
require_once __DIR__.'/../bing_webmaster.php';
if(!in_array($action,['bing_save','bing_test','bing_remove'],true))throw new InvalidArgumentException('Ação Bing inválida.');
if($action!=='bing_test'){
    $password=$_POST['confirm_password']??null;
    if(!is_string($password)||$password==='')throw new InvalidArgumentException('Informe a senha administrativa.');
    $stmt=$db->prepare('SELECT password_hash FROM users WHERE id=? AND active=1 LIMIT 1');
    $stmt->execute([(int)$me['id']]);
    if(!password_verify($password,(string)$stmt->fetchColumn()))throw new RuntimeException('Senha administrativa incorreta.');
    $https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off');
    $local=in_array((string)($_SERVER['REMOTE_ADDR']??''),['127.0.0.1','::1'],true);
    if(PHP_SAPI!=='cli'&&!$https&&!$local)throw new RuntimeException('Chaves privadas só podem ser administradas por HTTPS.');
}
if($action==='bing_save'){
    $key=$_POST['bing_api_key']??null;
    if(!is_string($key))throw new InvalidArgumentException('Chave Bing inválida.');
    if(trim($key)===''){
        if(igua_bing_read(IGUA_STORAGE)===null)throw new InvalidArgumentException('Informe a chave fornecida pelo Bing Webmaster Tools.');
    }else igua_bing_store(IGUA_STORAGE,$key);
    unset($_SESSION['igua_bing_result']);
    igua_security_event('settings_changed','bing_webmaster_configured');
    flash('Chave Bing salva em armazenamento privado. Use Testar e consultar para verificar sua propriedade.');
}elseif($action==='bing_remove'){
    if(($_POST['confirm_remove']??'')!=='1')throw new InvalidArgumentException('Confirme a remoção da credencial.');
    igua_bing_remove(IGUA_STORAGE);unset($_SESSION['igua_bing_result']);
    igua_security_event('settings_changed','bing_webmaster_removed');
    flash('Credencial Bing removida. IndexNow permanece independente.');
}else{
    $config=igua_bing_read(IGUA_STORAGE);
    if($config===null)throw new RuntimeException('Salve primeiro sua chave Bing.');
    $previous=(int)($_SESSION['igua_bing_attempt']??0);
    if(time()-$previous<30)throw new RuntimeException('Aguarde 30 segundos entre as consultas ao Bing.');
    $_SESSION['igua_bing_attempt']=time();
    $result=igua_bing_check($config['key'],setting('seo_site_url',''));
    $_SESSION['igua_bing_result']=$result+['checked_at'=>time()];
    igua_security_event('admin_action','bing_webmaster_read');
    flash($result['verified']?'Bing consultado: propriedade verificada, sitemaps listados.':'Bing respondeu: propriedade ainda não verificada ou não cadastrada.','info');
}
redirect('/admin.php?action=sitemap#bing-webmaster');
