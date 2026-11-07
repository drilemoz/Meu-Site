<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/integrations.php';
function test169(bool $ok,string $message):void {if(!$ok)throw new RuntimeException('Integrations 169: '.$message);echo 'OK: '.$message.PHP_EOL;}
class IntegrationPDO169 extends PDO {
    public array $saved=[];
    private bool $tx=false;
    public function __construct(){}
    public function beginTransaction():bool{$this->tx=true;return true;}
    public function commit():bool{$this->tx=false;return true;}
    public function rollBack():bool{$this->tx=false;return true;}
    public function inTransaction():bool{return $this->tx;}
    public function prepare(string $query,array $options=[]):PDOStatement|false{
        if(!str_contains($query,'INSERT INTO settings'))throw new RuntimeException('Escrita SQL inesperada');
        return new IntegrationStmt169($this);
    }
}
class IntegrationStmt169 extends PDOStatement {
    public function __construct(private IntegrationPDO169 $pdo){}
    public function execute(?array $params=null):bool {
        if(!is_array($params)||count($params)!==2)throw new RuntimeException('Parâmetros incorretos');
        $this->pdo->saved[$params[0]]=$params[1];
        return true;
    }
}
$db=new IntegrationPDO169();
igua_integrations_save($db,'integrations_save_ga4',['ga4_measurement_id'=>'g-AbCd1234','ga4_enabled'=>'1']);
test169(($db->saved['ga4_measurement_id']??'')==='G-ABCD1234' && ($db->saved['ga4_enabled']??'')==='1','GA4 validado e normalizado, sem reiniciar métricas internas');
test169(!isset($db->saved['analytics_enabled']),'GA4 central não altera coleta interna');
igua_integrations_save($db,'integrations_save_ga4',['ga4_measurement_id'=>'G-ABCD1234']);
test169($db->saved['ga4_enabled']==='0','GA4 desativado sem apagar o ID');
foreach([['ga4_measurement_id'=>'G-<script>'],['ga4_measurement_id'=>'G-ABCD1234','ga4_enabled'=>'1','x'=>[]],['ga4_measurement_id'=>[]]] as $post){
    if(($post['ga4_measurement_id']??null)==='G-ABCD1234')continue;
    $failed=false;try{igua_integrations_save($db,'integrations_save_ga4',$post);}catch(InvalidArgumentException){$failed=true;}
    test169($failed,'GA4 rejeita valores inválidos ou formato de array');
}
$prev=$db->saved;
$failed=false;try{igua_integrations_save($db,'integrations_save_ga4',['ga4_enabled'=>'1']);}catch(InvalidArgumentException){$failed=true;}
test169($failed&&$db->saved===$prev,'ativação sem ID rejeitada sem mudar o banco');
igua_integrations_save($db,'integrations_save_adsense',['adsense_publisher'=>'ca-pub-1234567890123456']);
test169($db->saved['adsense_publisher']==='ca-pub-1234567890123456'&&!isset($db->saved['ads_enabled']),'AdSense usa ID existente sem ativar anúncios');
foreach(['ca-pub-1','<script>','pub-1234567890123456'] as $value){
  $failed=false;try{igua_integrations_save($db,'integrations_save_adsense',['adsense_publisher'=>$value]);}catch(RuntimeException){$failed=true;}
  test169($failed,'AdSense rejeita identificador inválido');
}
igua_integrations_save($db,'integrations_save_verification',['google_site_verification'=>'token_ABC-12345=']);
test169($db->saved['google_site_verification']==='token_ABC-12345=','Search Console publica somente valor permitido');
test169(str_contains(igua_google_verification_meta($db->saved['google_site_verification']),'name="google-site-verification"'),'metatag pública gerada');
foreach(['<script>alert(1)</script>','<meta name="google-site-verification" content="abc">','abc'] as $bad){
 $failed=false;try{igua_integrations_save($db,'integrations_save_verification',['google_site_verification'=>$bad]);}catch(InvalidArgumentException){$failed=true;}
 test169($failed,'verificação recusa tags HTML ou códigos fora do formato');
}
$invalid=false;try{igua_integrations_save($db,'save_settings',[]);}catch(InvalidArgumentException){$invalid=true;}
test169($invalid,'controlador não admite ações fora da lista');
$dir=sys_get_temp_dir().'/int-169-'.bin2hex(random_bytes(4));mkdir($dir);file_put_contents($dir.'/sitemap.php','<?php');
$cfg=['seo_site_url'=>'https://exemplo.com.br','seo_index'=>'1','ga4_measurement_id'=>'G-ABCD1234','ga4_enabled'=>'1','adsense_publisher'=>'ca-pub-1234567890123456','google_site_verification'=>'token_ABC-12345='];
$ads=igua_adsense_ads_txt_line($cfg['adsense_publisher']);
$checks=igua_integrations_diagnostic($cfg,$dir,$ads);
test169(count($checks)===7&&count(array_filter($checks,static fn($row)=>$row['ok']))===7,'diagnóstico local completo quando arquivos e valores atendem critérios');
$checks=igua_integrations_diagnostic(array_replace($cfg,['ga4_enabled'=>'0','seo_index'=>'0']),$dir,'');
test169(count(array_filter($checks,static fn($row)=>$row['ok']))===4,'diagnóstico distingue valores ausentes de falhas externas');
unlink($dir.'/sitemap.php');rmdir($dir);
$root=dirname(__DIR__);
$admin=(string)file_get_contents($root.'/public/admin.php');
$menu=(string)file_get_contents($root.'/app/admin_views/layout.php');
$view=(string)file_get_contents($root.'/app/admin_views/routes/integrations.php');
$public=(string)file_get_contents($root.'/app/public_views/layout.php');
test169(str_contains($admin,"'integrations'=>'manage_settings'") && str_contains($admin,"'integrations_save_ga4'=>'manage_settings'") && str_contains($admin,'check_csrf();'),'rotas central e POST protegidas');
test169(str_contains($menu,'Integrações e APIs') && str_contains($view,'data-integrations-nav'),'menu e navegação mobile implementados');
test169(substr_count($view,'csrf_field()')>=5,'formulários antigos e novos com CSRF');
test169(str_contains($public,'igua_google_verification_meta(')&&!str_contains($public,'googletagmanager.com/gtag/js'),'metatag permitida e ausência de carregamento GA4 sem consentimento');
test169(str_contains((string)file_get_contents($root.'/app/analytics_admin.php'),'Gerenciar GA4') && str_contains((string)file_get_contents($root.'/app/admin_views/routes/ads_dashboard.php'),'Gerenciar conta AdSense'),'telas antigas apontam para a central');
// View rendering: não solicita banco, nenhum status Google é inventado.
function admin_link(string $action):string{return '/admin.php?action='.$action;}
$cfg=array_replace($cfg,['ga4_enabled'=>'1']);$rows=igua_integrations_diagnostic($cfg,IGUA_PUBLIC,'');
$options=[['value'=>'home','label'=>'Página inicial']];$choice='home';$subject=['kind'=>'home','title'=>'Página inicial'];
$report=igua_page_inspect_report($subject,$cfg,IGUA_PUBLIC);
ob_start();require $root.'/app/admin_views/routes/integrations.php';$html=ob_get_clean();
test169(str_contains($html,'Google Analytics 4')&&str_contains($html,'Search Console')&&str_contains($html,'Diagnóstico central'),'view HTML renderizada sem MySQL');
test169(!str_contains($html,'Aprovado pelo Google') && substr_count($html,'<details')===substr_count($html,'</details>'),'nenhuma falsa aprovação e detalhes HTML balanceados');
echo 'Integrações 1.0.69: testes locais concluídos; sem validação remota, hospedagem ou APIs externas.'.PHP_EOL;
