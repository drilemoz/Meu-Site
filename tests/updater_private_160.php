<?php
declare(strict_types=1);
// Ambiente independente e respostas HTTP simuladas: nenhum token real nem rede.
$root=sys_get_temp_dir().'/igua-private-'.bin2hex(random_bytes(8));
mkdir($root,0700);mkdir($root.'/storage',0700);mkdir($root.'/public',0700);
define('IGUA_ROOT',$root);define('IGUA_STORAGE',$root.'/storage');define('IGUA_PUBLIC',$root.'/public');
putenv('IGUA_DEPLOY_VPS=');
file_put_contents($root.'/VERSION',"1.0.159\n");
require __DIR__.'/../app/updater.php';
require __DIR__.'/../app/migration_package.php';
function expect160(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
function fails160(callable $f,string $message):void{try{$f();}catch(RuntimeException $e){expect160(str_contains($e->getMessage(),$message),'Erro inesperado: '.$e->getMessage());return;}throw new RuntimeException('Esperava falha: '.$message);}
$token='github_pat_'.str_repeat('A',40);$calls=[];$scenario='release';
$base='https://api.github.com/repos/drilemoz/Meu-Site';
$http=static function(string $url,int $max,string $auth,bool $binary)use(&$calls,&$scenario,$base,$token):string{
    $calls[]=[$url,$auth,$binary];
    if($scenario==='unauthorized')throw new RuntimeException('HTTP 401');
    if($scenario==='hidden')throw new RuntimeException('HTTP 404');
    if($url===$base)return json_encode(['full_name'=>'drilemoz/Meu-Site','private'=>true]);
    if($url===$base.'/releases/latest'){
        if($scenario==='empty')throw new RuntimeException('HTTP 404');
        return json_encode(['tag_name'=>'v1.0.160','body'=>'teste','assets'=>[['name'=>'Atualizacao-v1.0.160.zip','size'=>100,'id'=>12345,'browser_download_url'=>'https://example.com/should-not-use']]]);
    }
    expect160($url===$base.'/releases/assets/12345'&&$auth===$token&&$binary,'Asset autenticado por ID');
    return "PK\x03\x04fake-test-data";
};
try{
    expect160(!igua_updater_github_configured(),'Inicialmente sem token');
    fails160(fn()=>igua_updater_github_save("github_pat_bad\r\nX: fake",$http),'Token inválido');
    igua_updater_github_save($token,$http);
    expect160(igua_updater_github_configured(),'Token salvo');
    expect160((fileperms(IGUA_UPDATE_GITHUB_AUTH)&0777)===0600,'Modo 0600');
    expect160(!igua_migration_extra_allowed('storage/updater/github-auth.json'),'Token excluído da migração');
    expect160(in_array('Authorization: Bearer '.$token,igua_updater_http_headers($base,$token,false,0),true),'Cabeçalho de autenticação');
    foreach(['https://release-assets.githubusercontent.com/blob','https://objects.githubusercontent.com/blob','https://github.com/blob','https://api.github.com/redirect'] as $url)
        expect160(!str_contains(implode(' ',igua_updater_http_headers($url,$token,true,1)),$token),'Token não atravessa redirect');
    expect160(in_array('Accept: application/octet-stream',igua_updater_http_headers($base.'/releases/assets/12345',$token,true,0),true),'Download binário');
    $release=igua_updater_remote_release(false,$http);
    expect160($release['newer']&&$release['url']===$base.'/releases/assets/12345','Release mais recente e URL por ID');
    $n=count($calls);igua_updater_remote_release(false,$http);expect160(count($calls)===$n,'Cache válido');
    igua_updater_remote_release(true,$http);expect160(count($calls)===$n+1,'Consulta forçada');
    expect160(!str_contains(file_get_contents(IGUA_UPDATE_CACHE),$token),'Cache sem token');
    $file=igua_updater_download_release($release['url'],$http);expect160(str_starts_with(file_get_contents($file),'PK'),'Download simulado');unlink($file);
    fails160(fn()=>igua_updater_download_release('https://api.github.com/repos/other/repo/releases/assets/1',$http),'Destino');
    $scenario='empty';expect160(igua_updater_remote_release(true,$http)===null,'Repo acessível sem release');
    $scenario='hidden';fails160(fn()=>igua_updater_remote_release(true,$http),'HTTP 404');
    $scenario='unauthorized';fails160(fn()=>igua_updater_github_save('github_pat_'.str_repeat('B',40),$http),'HTTP 401');
    expect160(igua_updater_github_auth()['token']===$token,'Falha preserva token anterior');
    expect160(igua_updater_diagnostic(new RuntimeException('HTTP 401'))['code']==='github_auth','Diagnóstico de token inválido');
    $scenario='release';putenv('IGUA_DEPLOY_VPS=1');
    igua_updater_github_save($token,$http);clearstatcache();
    expect160((fileperms(IGUA_UPDATE_GITHUB_AUTH)&0777)===0640,'Modo VPS 0640 compartilhado com worker');
    expect160(igua_updater_remote_release(true,$http)['newer'],'Worker usa a mesma credencial');
    expect160(!is_file(IGUA_UPDATE_CACHE),'Worker não grava cache web');
    igua_updater_github_remove();expect160(!igua_updater_github_configured(),'Remoção do token');
    putenv('IGUA_DEPLOY_VPS=');
    $release=igua_updater_remote_release(true,$http);expect160(end($calls)[1]===''&&$release['newer'],'Consulta pública sem credencial');
    echo "OK: token privado, download por ID, cache, 404, 401, remoção e worker VPS (HTTP simulado)\n";
}finally{
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach($it as $entry){$entry->isDir()&&!$entry->isLink()?rmdir($entry->getPathname()):unlink($entry->getPathname());}rmdir($root);
}
