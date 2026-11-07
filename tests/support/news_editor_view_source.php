<?php
declare(strict_types=1);
/** Fonte HTML/PHP real dos parciais do editor, em ordem de exibição, para testes estruturais. */
function igua_news_editor_view_source(): string {
    $dir=dirname(__DIR__,2).'/app/news_admin_view/';
    $names=['header.php','details.php','blocks.php','media.php','footer.php','revisions.php','scripts.php'];
    $result='';
    foreach($names as $name){
        $contents=file_get_contents($dir.$name);
        if($contents===false)throw new RuntimeException('Template editorial ausente: '.$name);
        $result.=$contents;
    }
    return $result;
}
