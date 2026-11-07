<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/editor_examples.php';
$examples=igua_editor_examples();
if(count($examples)!==3)throw new RuntimeException('Número incorreto de exemplos.');
$ids=[];
foreach($examples as $item){
 if(!str_starts_with($item['title'],'EXEMPLO FICTÍCIO:')||!str_contains($item['body'],'fictíc'))throw new RuntimeException('Exemplo sem identificação de ficção.');
 if(in_array($item['slug'],$ids,true))throw new RuntimeException('Slug duplicado.');$ids[]=$item['slug'];
 if(!is_file(__DIR__.'/../public/assets/img/'.substr($item['image'],5)))throw new RuntimeException('Imagem demonstrativa ausente.');
 $blocks=normalize_news_blocks(json_encode($item['blocks'],JSON_THROW_ON_ERROR));
 if(count($blocks)<3||!array_filter($blocks,static fn($b)=>$b['kind']==='section' && $b['collapsed']))throw new RuntimeException('Faltam seções de exemplo.');
 if(strlen(news_seo_description($item['title'],$item['summary'],$item['body']))>320)throw new RuntimeException('SEO muito longo.');
}
echo "OK: três matérias fictícias estruturadas e imagens existentes.\n";
