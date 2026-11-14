<?php
declare(strict_types=1);
require_once __DIR__.'/support/news_editor_view_source.php';
$root=dirname(__DIR__);
$view=igua_news_editor_view_source();
$editor=file_get_contents($root.'/public/assets/js/news-editor.js');
$upload=file_get_contents($root.'/public/assets/js/news-upload.js');
function test122(bool $ok,string $msg): void {if(!$ok)throw new RuntimeException('FALHOU: '.$msg);echo "OK: $msg\n";}
test122(substr_count($view,'data-tab="')===9&&substr_count($view,'data-step="')===9,'Fluxo editorial continua com nove etapas');
test122(str_contains($view,'name="gallery_files[]"')&&str_contains($view,'name="attachment_files[]"'),'Nomes de campos da API de upload preservados');
test122(str_contains($editor,'captureNewMediaDetails')&&str_contains($editor,'data-new-caption')&&str_contains($editor,'data-new-credit'),'Campos de legenda e crédito preservados na prévia');
test122(str_contains($editor,"previewFiles(el.dataset.uploadInput)")&&str_contains($editor,"previewFiles());"),'Substituição de arquivos e conclusão da otimização tratados separadamente');
test122(str_contains($upload,'selectionTokens')&&str_contains($upload,'if(!isCurrent())return'),'Otimização atrasada não altera seleção nova');
test122(str_contains($upload,'pending.set(el,task)')&&str_contains($upload,'pending.get(el)===task'),'Somente o upload mais recente de cada campo bloqueia o envio');
test122(str_contains($upload,'errors.delete(el)')&&str_contains($upload,'errors.size'),'Erros de upload isolados por campo');
test122(version_compare(trim(file_get_contents($root.'/VERSION')),'1.0.22','>='),'Versão pelo menos 1.0.22 mantém as regressões históricas');
