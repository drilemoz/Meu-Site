<?php
declare(strict_types=1);
require_once __DIR__ . '/support/entry_view_source.php';
/** Confere o CSS montado; pode rodar apenas com PHP, sem Python ou navegador. */
$root=dirname(__DIR__);
$css=$root.'/public/assets/css';
$modules=glob($css.'/modules/*.css');
if($modules===false)throw new RuntimeException('Não foi possível listar os módulos CSS.');
sort($modules,SORT_STRING);
function css131_assert(bool $ok,string $label): void {
    if(!$ok)throw new RuntimeException('Falha: '.$label);
    echo 'OK: '.$label."\n";
}
css131_assert(count($modules)===20,'20 módulos CSS separados por área');
$expected=['foundation','installer','editorial-articles','transport','site-layout-contact','advertising','editor-structured','analytics','editorial-admin','health','editor-visual','media-library','editor-mobile','seo','archive','security','legal-apps','file-manager','server-info','dns-diagnostic'];
foreach($expected as $i=>$label){
    css131_assert(basename($modules[$i])===sprintf('%02d-%s.css',$i+1,$label),'ordem de módulo '.($i+1));
}
$composed='';
foreach($modules as $module){$bytes=file_get_contents($module);css131_assert($bytes!==false && $bytes!=='','módulo não vazio '.basename($module));$composed.=$bytes;}
$delivered=file_get_contents($css.'/style.css');
css131_assert($delivered!==false && hash_equals(hash('sha256',$composed),hash('sha256',$delivered)) && $composed===$delivered,'arquivo distribuído preserva ordem e conteúdo exatos dos módulos');
css131_assert(!str_contains($delivered,'@import'),'CSS pronto em um único recurso sem cadeia de imports');
foreach(['public/admin.php','public/index.php','public/setup.php'] as $page){
    $html=igua_refactor_page_source($page);
    css131_assert($html!==false && str_contains($html,'/assets/css/style.css'),'entrada CSS compatível em '.$page);
}
css131_assert(is_file($root.'/tools/build_css.py'),'script para reconstrução do CSS entregue');
echo "CSS 1.0.31: validação concluída.\n";
