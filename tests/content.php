<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
$path=__DIR__.'/../app/sample_news.json';
$news=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
if (count($news)!==100) throw new RuntimeException('Esperados 100 registros de amostra.');
$counts=[];$slugs=[];
foreach ($news as $i=>$a) {
    $counts[$a['category']]=($counts[$a['category']]??0)+1;
    $slug=slug($a['title']);
    if(isset($slugs[$slug])) throw new RuntimeException('Título/slug duplicado: '.$slug);
    $slugs[$slug]=true;
    foreach (['title','summary','body','image','image_caption','image_credit','source_name','source_url','published_at'] as $key) {
        if(empty($a[$key])) throw new RuntimeException("Registro {$i} sem {$key}");
    }
    if (!str_starts_with($a['source_url'],'https://') || !filter_var($a['source_url'],FILTER_VALIDATE_URL)) throw new RuntimeException('Fonte inválida');
    if(ulen($a['title'])>220 || ulen($a['summary'])>480 || ulen($a['image_caption'])>360 || ulen($a['body'])<100) throw new RuntimeException('Campo fora dos limites.');
    if (!str_starts_with($a['image'],'demo-') || !is_file(__DIR__.'/../public/assets/img/'.substr($a['image'],5))) throw new RuntimeException('Imagem não localizada: '.$a['image']);
}
if (count($counts)!==10 || count(array_filter($counts,static fn($n)=>$n===10))!==10) throw new RuntimeException('Editorias sem 10 textos: '.json_encode($counts));
print 'OK: 100 textos, 10 por cada uma das 10 editorias, fontes HTTPS, legendas, imagens existentes, títulos únicos e limites SQL.' . PHP_EOL;
