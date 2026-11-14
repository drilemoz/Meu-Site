<?php
declare(strict_types=1);
/** Diagnóstico de planos EXPLAIN. CLI somente leitura, sem mostrar senha ou registros. */
if (PHP_SAPI!=='cli') {http_response_code(403);exit;}
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/news_archive.php';
require_once __DIR__.'/../app/news_query_plans.php';
if (in_array('--help',$argv,true)) {
    echo "Uso: php tools/news_explain.php\nConexão: storage/config.php ou IGUANEWS_DB_HOST/PORT/NAME/USER/PASSWORD.\nExecuta somente EXPLAIN (não altera o banco).\n";
    exit;
}
try {
    if (!extension_loaded('pdo_mysql')) throw new RuntimeException('Extensão pdo_mysql não instalada.');
    foreach (igua_news_explain(db()) as $name=>$plans) {
        echo "\n== ".$name." ==\n";
        foreach ($plans as $plan) {
            echo sprintf("tabela=%s acesso=%s indice=%s linhas_estimadas=%d extra=%s\n",
                $plan['table'],$plan['type'],$plan['key']?:'(nenhum)',$plan['estimated_rows'],$plan['extra']);
        }
    }
    echo "\nOs números são estimativas do otimizador, não tempos de execução.\n";
} catch (Throwable $error) {
    fwrite(STDERR,"EXPLAIN indisponível: ".$error->getMessage()."\n");
    exit(1);
}
