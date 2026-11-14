<?php
declare(strict_types=1);
/**
 * Fonte composta dos entrypoints PHP para verificacoes estaticas de regressao.
 * Verifica os arquivos reais: public/* nao contem mais todo o HTML.
 */
function igua_refactor_page_source(string $entry): string {
    $root=dirname(__DIR__,2);
    $paths=match($entry) {
        'public/admin.php'=>[
            'public/admin.php', 'app/admin_views/login.php', 'app/admin_views/health.php',
            'app/admin_views/layout.php', 'app/admin_views/pages.php',
            ...array_map(static fn(string $n): string=>'app/admin_views/routes/'.$n.'.php',
                ['dashboard','articles','editor','categories','authors','sources','users','settings','backups'])
        ],
        'public/index.php'=>[
            'public/index.php', 'app/public_views/layout.php', 'app/public_views/pages.php',
            ...array_map(static fn(string $n): string=>'app/public_views/routes/'.$n.'.php',
                ['bus','contact','privacy','terms','home','article','category','search'])
        ],
        'public/setup.php'=>['public/setup.php'],
        default=>throw new InvalidArgumentException('Entrada desconhecida: '.$entry)
    };
    $parts=[];
    foreach($paths as $relative){
        $content=file_get_contents($root.'/'.$relative);
        if($content===false)throw new RuntimeException('Componente ausente: '.$relative);
        $parts[]=$content;
    }
    return implode("\n",$parts);
}
