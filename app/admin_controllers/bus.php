<?php
declare(strict_types=1);
// Ações administrativas privadas: executadas após autenticação e validação CSRF.
        if (in_array($action,['bus_import_teixeira','bus_stage_import','bus_approve_import','bus_ignore_import','bus_toggle_fixed','save_bus_route','save_bus_wizard','save_bus_departure','toggle_bus_departure','delete_bus_departure','delete_bus_route'],true)) bus_admin_post($db,$action,$me);
