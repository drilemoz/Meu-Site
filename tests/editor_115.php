<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/news_editor.php';
function expect_115(bool $ok,string $msg): void {if(!$ok)throw new RuntimeException('Falhou: '.$msg);echo "OK: $msg\n";}
$fmt=news_text_format("## Subtítulo\n\nTexto com **negrito** e *itálico*.\n\n- Primeiro item\n- Segundo item");
expect_115(str_contains($fmt,'<h2>Subtítulo</h2>')&&str_contains($fmt,'<strong>negrito</strong>')&&str_contains($fmt,'<em>itálico</em>')&&str_contains($fmt,'<ul class="news-list">'),'formatação simples e lista');
$bad=news_text_format("<script>alert('x')</script> **alerta** <img src=x onerror=alert(1)>");
expect_115(!str_contains($bad,'<script>')&&!str_contains($bad,'<img ')&&str_contains($bad,'&lt;script&gt;'),'HTML malicioso sempre escapado');
expect_115(news_library_resolve('../config.php')===null&&news_library_resolve('demo-falsa.jpg')===null,'impede traversal e arquivos arbitrários');
$dir=IGUA_PUBLIC.'/uploads';$name=str_repeat('a',32).'.png';$file=$dir.'/'.$name;
$png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL/nwAAAABJRU5ErkJggg==',true);
if($png===false)throw new RuntimeException('PNG de teste inválido');
file_put_contents($file,$png);
try{$valid=news_library_resolve($name);expect_115($valid!==null&&$valid['mime']==='image/png','reutilização apenas de imagem local válida');}finally{unlink($file);}
expect_115(news_library_resolve($name)===null,'não aceita mídia removida');
echo "Testes do Editor 2.0 aprovados.\n";
