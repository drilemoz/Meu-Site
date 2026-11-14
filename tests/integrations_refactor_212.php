<?php
declare(strict_types=1);
require_once __DIR__.'/support/integrations_source.php';

// Baseline atualizado intencionalmente na 1.0.119 para a nova interface GA4.
// Preserva o contrato de nove componentes e detecta mudanças adicionais não revisadas.
$original='2daf024dc2cee3d7ea41d599b8543bf1ebbc1357750cf317f6eb7e53025d175a';
$assembled=igua_test_integrations_source();
if (hash('sha256',$assembled)!==$original) {
    throw new RuntimeException('A divisão dos componentes alterou o template de Integrações.');
}
$dir=dirname(__DIR__).'/app/admin_views/routes/integrations';
$components=['ga4','adsense','search_console','pagespeed','gemini',
             'diagnostics','page_inspector','catalog','dialogs'];
$root=(string)file_get_contents(dirname(__DIR__).'/app/admin_views/routes/integrations.php');
foreach ($components as $component) {
    $path=$dir.'/'.$component.'.php';
    if (!is_file($path) || !str_contains($root,"require __DIR__.'/integrations/".$component.".php'")) {
        throw new RuntimeException('Componente ausente ou não importado: '.$component);
    }
}
if (substr_count($root,"require __DIR__.'/integrations/")!==count($components)) {
    throw new RuntimeException('Template possui includes inesperados.');
}
foreach (['integrations_save_ga4','integrations_save_adsense',
         'integrations_gemini_save','integrations_gemini_test',
         'integrations_search_console_inspect','csrf_field()','confirm_password',
         'id="int-dialog"','id="gemini-test-dialog"'] as $expected) {
    if (!str_contains($assembled,$expected)) {
        throw new RuntimeException('Contrato perdido na divisão: '.$expected);
    }
}
if (str_contains($assembled,'private_key')) {
    throw new RuntimeException('Referência a credencial privada no template.');
}
echo "Integrações 1.0.112: 9 componentes, template preservado byte a byte, ações e segurança verificadas.\n";
