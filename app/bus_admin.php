<?php
declare(strict_types=1);
/** Fachada de compatibilidade: mantenha estas assinaturas públicas. */
require_once __DIR__.'/bus.php';
require_once __DIR__.'/bus_import.php';
require_once __DIR__.'/bus_wizard.php';
require_once __DIR__.'/bus_admin/post_import.php';
require_once __DIR__.'/bus_admin/post_routes.php';
require_once __DIR__.'/bus_admin/post_departures.php';
require_once __DIR__.'/bus_admin/view_import.php';
require_once __DIR__.'/bus_admin/view_routes.php';

function bus_admin_post(PDO $db,string $action,array $me): never {
    require_ability('manage_bus',$me);
    if (in_array($action,['bus_stage_import','bus_approve_import','bus_ignore_import'],true)) bus_admin_post_import($db,$action);
    if (in_array($action,['bus_toggle_fixed','save_bus_wizard','save_bus_route','delete_bus_route'],true)) bus_admin_post_routes($db,$action);
    if (in_array($action,['save_bus_departure','toggle_bus_departure','delete_bus_departure'],true)) bus_admin_post_departures($db,$action);
    throw new RuntimeException('Ação inválida para ônibus.');
}
function bus_admin_view(PDO $db,string $action): void {
    if ($action==='bus_import') {bus_admin_view_import($db,$action);return;}
    if ($action==='bus') {bus_admin_view_routes($db,$action);return;}
    bus_wizard_view($db,(int)($_GET['id']??0));
}
