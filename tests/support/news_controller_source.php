<?php
declare(strict_types=1);
/**
 * Reconstrói o controlador editorial para testes estáticos de regressão.
 * Não avalia PHP nem inclui os controladores (que processariam POST/redirect).
 */
function igua_test_news_controller_source(): string {
    $root=dirname(__DIR__,2);
    $controller=(string)file_get_contents($root.'/app/admin_controllers/news.php');
    $expected=['taxonomy','gemini','drafts','save','lifecycle'];
    $found=[];
    $combined=preg_replace_callback(
        "~^require __DIR__.'/news/([a-z]+)\\.php';\\n~m",
        static function(array $match)use($root,&$found): string {
            $name=$match[1];
            $allowed=['taxonomy','gemini','drafts','save','lifecycle'];
            if(!in_array($name,$allowed,true))throw new RuntimeException('Fragmento inesperado: '.$name);
            $file=$root.'/app/admin_controllers/news/'.$name.'.php';
            if(!is_file($file))throw new RuntimeException('Fragmento ausente: '.$name);
            $fragment=(string)file_get_contents($file);
            if(!str_starts_with($fragment,"<?php\n"))throw new RuntimeException('Cabeçalho PHP inválido: '.$name);
            $found[]=$name;
            return substr($fragment,6);
        },
        $controller
    );
    if($combined===null || $found!==$expected)throw new RuntimeException('Ordem de controladores incorreta.');
    return $combined;
}
