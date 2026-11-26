<?php
declare(strict_types=1);
require_once __DIR__.'/../app/news_editor/links.php';
function link159(bool $ok,string $label):void {if(!$ok)throw new RuntimeException($label);echo "OK: $label\n";}
link159(news_reference_links("  https://example.org/a\r\n\nhttp://example.org/b?q=1&v=2\nhttps://example.org/a")===['https://example.org/a','http://example.org/b?q=1&v=2'],'normalização e duplicação');
link159(news_reference_links(null)===[]&&news_reference_links('')===[],'campo opcional e remoção');
foreach(['javascript:alert(1)','data:text/html,test','ftp://example.org','//example.org','https://user:password@example.org','https://example.org/a b','https://example.org/'.str_repeat('a',2048),['https://example.org'],implode("\n",array_map(fn($n)=>"https://example.org/$n",range(1,11)))] as $bad){$rejected=false;try{news_reference_links($bad);}catch(RuntimeException){$rejected=true;}link159($rejected,'rejeita entrada inválida');}
link159(news_reference_links("javascript:alert(1)\nhttps://example.org",false)===['https://example.org'],'leitura defensiva de dados armazenados');
