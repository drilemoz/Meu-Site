<?php
declare(strict_types=1);
/** Regressão da página em branco e menu inerte após falha de diagnóstico (v1.0.137). */
$root=dirname(__DIR__);
$checks=0;
function check237(bool $condition, string $reason): void {
    global $checks;
    $checks++;
    if(!$condition)throw new RuntimeException('Falhou admin_diagnostic_render_237: '.$reason);
}
$controller=file_get_contents($root.'/public/admin.php');
check237(str_contains($controller,"require_once __DIR__.'/../app/server_info.php';"),'carregamento do serviço de informações do servidor');
check237(str_contains($controller,"require_once __DIR__.'/../app/dns_diagnostic.php';"),'carregamento do serviço DNS');
check237(strpos($controller,"require_once __DIR__.'/../app/server_info.php';")<strpos($controller,"require_once __DIR__.'/../app/dns_diagnostic.php';"),'serviços carregados antes das rotas');
$layout=file_get_contents($root.'/app/admin_views/layout.php');
$navScript='<script src="/assets/js/admin-ui.js?v=<?=h(igua_updater_version())?>" defer></script>';
check237(substr_count($layout,$navScript)===1,'script de menu incluído exatamente uma vez');
check237(strpos($layout,$navScript)<strpos($layout,'<body class="admin-body'),'menu carregado no head, mesmo com falha posterior de renderização');
check237(str_contains($layout,'id="admin-nav-dialog"') && str_contains($layout,'id="admin-nav-toggle"'),'dialog e botão reais preservados');
// Exercita os templates reais sem banco nem consultas de rede.
require $root.'/app/server_info.php';
function h(mixed $v): string {return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function admin_link(string $a): string{return '/admin.php?action='.rawurlencode($a);}
function setting(string $key,string $default=''):string{return 'https://iguatama.online';}
function igua_dns_snapshot(string $siteUrl): array {throw new RuntimeException('Simulação de indisponibilidade do serviço DNS: caminho privado interno');}
$GLOBALS['render237_log']=[];
function igua_health_log(string $code,Throwable $e): void {$GLOBALS['render237_log'][]=$code;}
$action='server_info';$db=null;
ob_start();require $root.'/app/admin_views/pages.php';$html=ob_get_clean();
check237(str_contains($html,'Recursos disponíveis para o site'),'renderização completa de Informações do Servidor');
check237(str_contains($html,'Configurações do PHP'),'seções finais disponíveis');
check237(!str_contains($html,'Diagnóstico indisponível'),'sem fallback quando a página funciona');
$action='dns_diagnostic';
ob_start();require $root.'/app/admin_views/pages.php';$fallback=ob_get_clean();
check237(str_contains($fallback,'Diagnóstico indisponível'),'falha vira mensagem legível, não página branca');
check237(str_contains($fallback,'Central de Saúde'),'saída de recuperação');
check237(!str_contains($fallback,'caminho privado interno'),'não vazar detalhes da exceção');
check237(($GLOBALS['render237_log'][0]??'')==='ADMIN_DIAGNOSTIC_VIEW_FAILED','erro interno registrado de forma restrita');
check237(http_response_code()===500,'falha refletida no status HTTP');
echo "OK admin_diagnostic_render_237: {$checks} verificações.\n";
