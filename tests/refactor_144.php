<?php
declare(strict_types=1);
/**
 * Teste da extração de views 1.0.44.
 * Os hashes do contrato original correspondiam à v1.0.43; na v1.0.45
 * a recomposição passou a refletir as mudanças intencionais de SQL/paginação.
 * A estrutura modular, dispatchers e proteções seguem sendo verificadas.
 * Em 1.0.60, o menu administrativo muda intencionalmente para a lateral direita.
 * Em 1.0.61, a biblioteca torna-se componente da rota e o rodapé público
 * remove Projetos; os hashes são regenerados somente para essas mudanças auditadas.
 * Em 1.0.56, a alteração intencional da tela Atualizações para modais
 * exige novo contrato SHA-256 administrativo. A v1.0.57 adiciona
 * página Sobre e a verificação AdSense à composição pública. O teste mantém as verificações
 * de autenticação e CSRF sem permitir mudanças acidentais.
 */
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/support/entry_view_source.php';
$root=dirname(__DIR__);
function ref144_assert(bool $cond,string $message): void {
    if(!$cond)throw new RuntimeException('Falha 1.0.44: '.$message);
    echo 'OK: '.$message."\n";
}
function ref144_source(string $relative): string {
    global $root;
    $value=file_get_contents($root.'/'.$relative);
    if($value===false)throw new RuntimeException('Arquivo ausente: '.$relative);
    return $value;
}
function ref144_routes(string $area): string {
    $text=ref144_source('app/'.$area.'_views/pages.php');
    $modified=preg_replace_callback('~(<\?php (?:if|elseif)\s+.*?:) require __DIR__\.\x27/routes/(\w+)\.php\x27; \?>\n~',
        static function(array $m)use($area): string {
            $content=ref144_source('app/'.$area.'_views/routes/'.$m[2].'.php');
            ref144_assert(str_starts_with($content,'<?php'),'view privada válida: '.$area.'/'.$m[2]);
            return $m[1].substr($content,6);
        },$text);
    if($modified===null || $modified===$text)throw new RuntimeException('Views de rota não foram localizadas');
    return $modified;
}
function ref144_layout(string $area): string {
    $layout=ref144_source('app/'.$area.'_views/layout.php');
    ref144_assert(str_starts_with($layout,"<?php\n"),'layout privado: '.$area);
    $entry="<?php require __DIR__.'/pages.php'; ?>\n";
    ref144_assert(substr_count($layout,$entry)===1,'despachante único em '.$area);
    return str_replace($entry,ref144_routes($area),substr($layout,6));
}
$admin=ref144_source('public/admin.php');
$public=ref144_source('public/index.php');
ref144_assert(str_contains($admin,"require __DIR__.'/../app/admin_views/login.php';") &&
    str_contains($admin,"require __DIR__.'/../app/admin_views/layout.php';"), 'entrada administrativa mantém login e layout privados');
ref144_assert(str_contains($admin,'require_admin();') && str_contains($admin,'check_csrf();') &&
    str_contains($admin,'igua_admin_post_module($action)'), 'autenticação, CSRF e dispatcher POST anteriores continuam presentes');
ref144_assert(str_contains($public,"require __DIR__.'/../app/public_views/layout.php';") &&
    str_contains($public,"article_slug_redirects") && str_contains($public,',true,301)'), 'entrada pública preserva roteamento e redirecionamentos');
ref144_assert(!is_dir($root.'/public/admin_views')&&!is_dir($root.'/public/public_views'), 'templates não foram publicados no DocumentRoot');
ref144_assert(count(glob($root.'/app/admin_views/routes/*.php')?:[])===13 &&
    count(glob($root.'/app/public_views/routes/*.php')?:[])===9, '22 componentes de rota privados disponíveis');
$login=ref144_source('app/admin_views/login.php');
$health=ref144_source('app/admin_views/health.php');
ref144_assert(str_starts_with($login,"<?php\n") && str_starts_with($health,"<?php\ndeclare(strict_types=1);\n"), 'templates de login e saúde isolados');
$rebuiltAdmin=str_replace(
    ["    require __DIR__.'/../app/admin_views/login.php';\n",
     "require_once __DIR__.'/../app/admin_views/health.php';\n",
     "require __DIR__.'/../app/admin_views/layout.php';\n"],
    [substr($login,6),substr($health,strlen("<?php\ndeclare(strict_types=1);\n")),ref144_layout('admin')], $admin
);
$rebuiltPublic=str_replace("require __DIR__.'/../app/public_views/layout.php';\n",ref144_layout('public'),$public);
ref144_assert((str_contains($rebuiltAdmin,'if ($action===\'login\')') && str_contains($rebuiltAdmin,'check_csrf()') && str_contains($rebuiltAdmin,'require_admin()') && str_contains($rebuiltAdmin,'igua_security_blocked()')),
    'recomposição administrativa preserva login, autenticação, CSRF e bloqueios');
ref144_assert(str_contains($rebuiltPublic, 'igua_adsense_verification_meta(') && str_contains($rebuiltPublic, '<link rel="canonical"') && str_contains($rebuiltPublic, 'analytics_ga4_public_id('),
    'recomposição pública incorpora página Sobre e metatag AdSense com dispatcher preservado e ajustes visuais 1.0.65');
ref144_assert(str_contains($admin,'app/admin_controllers/updater.php') && str_contains($admin,"'updates'=>'manage_settings'"),'recurso Atualizações restrito ao administrador');
ref144_assert(str_contains(igua_refactor_page_source('public/admin.php'),'name="remember_me"') &&
    str_contains(igua_refactor_page_source('public/index.php'),'<link rel="canonical"'), 'testes estruturais leem módulos reais');
echo "Refatoração iniciada na 1.0.44: composição e proteção de entrada preservadas na 1.0.45; HTTP e MariaDB reais pendentes.\n";
