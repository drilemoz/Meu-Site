<?php
declare(strict_types=1);
require __DIR__.'/../app/news_editor/format.php';
function h($x):string{return htmlspecialchars((string)$x,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function check157(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$body="O governo anunciou mudanças nas regras fiscais.\n\nNovas regras para os gastos públicos\n\nA legislação amplia os instrumentos disponíveis ao Executivo federal para controlar despesas e buscar o cumprimento das metas fiscais.";
check157(str_contains(news_text_format($body),'<h2>Novas regras para os gastos públicos</h2>'),'Intertítulo reconhecido');
foreach(['Uma frase comum.','- Um item da lista','<script>alert(1)</script>','Uma frase curta,','**Destaque explícito**'] as $line){$s=str_replace('Novas regras para os gastos públicos',$line,$body);check157(!str_contains(news_text_format($s),'<h2>'),'Não promover '.$line);}
check157(str_contains(news_text_format('## Título manual'),'<h2>Título manual</h2>'),'Markdown explícito');
check157(news_auto_headings('Uma única linha sem contexto')==='Uma única linha sem contexto','Sem contexto não promove');
$editor=file_get_contents(__DIR__.'/../app/news_admin_view/details.php');
check157(str_contains($editor,'data-news-taxonomy="source"')&&!str_contains($editor,'Autor da notícia')&&str_contains($editor,'type="hidden" name="source_name"'),'Classificação compacta');
echo "OK: intertítulos, limites, Markdown e classificação\n";
