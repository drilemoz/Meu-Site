<?php
declare(strict_types=1);
require_once __DIR__ . '/support/entry_view_source.php';
require_once __DIR__.'/support/news_editor_view_source.php';
$root=dirname(__DIR__);
function check119(bool $ok,string $label):void {if(!$ok)throw new RuntimeException('FAIL: '.$label);echo "OK: $label\n";}
$view=igua_news_editor_view_source();
$js=file_get_contents($root.'/public/assets/js/news-editor.js');
$manager=file_get_contents($root.'/public/assets/js/media-library.js');
$files=file_get_contents($root.'/public/assets/js/file-inputs.js');
$css=file_get_contents($root.'/public/assets/css/style.css');
$admin=igua_refactor_page_source('public/admin.php');
check119(str_contains($view,'id="news-visual-editor"'),'editor visual segue disponível na nova interface');
check119(str_contains($js,'function changeTab(index'),'navegação de etapas mantém alternância de visibilidade');
check119(str_contains($view,'id="news-visual-editor"') && str_contains($js,'function markdownFromVisual()'),'editor visual mantém texto seguro em campo original');
check119(str_contains($js,"e.clipboardData.getData('text/plain')"),'colagem não injeta HTML em notícia');
check119(substr_count($view,'data-media-pick="cover"')===1,'apenas um atalho de capa');
check119(substr_count($view,'data-media-pick="gallery"')===1,'apenas um atalho de galeria');
check119(!str_contains($manager,"button.textContent='🖼️ Abrir gerenciador de imagens'"),'sem botão extra duplicado via JavaScript');
check119(str_contains($view,'news-cover-preview') && str_contains($css,'max-height:166px'),'capa atual compacta');
check119(str_contains($view,'news-direct-upload') && str_contains($view,'data-upload-input="attachment"'),'upload direto e documentos preservados');
check119(str_contains($files,'input.click()') && str_contains($files,"input.addEventListener('change',update)"),'seletor acessível mantém input de upload real');
check119(str_contains($admin,'news-articles-table') && str_contains($css,'.news-articles-table tr'),'notícias viram cartões no celular');
check119(str_contains($admin,'/assets/js/file-inputs.js') && str_contains(file_get_contents($root.'/public/setup.php'),'/assets/js/file-inputs.js'),'seletores aplicados a painel e instalador');
check119(str_contains($view,'name="library_cover"') && str_contains($view,'name="attachment_files[]"'),'campos de dados anteriores preservados');
echo "Editor 1.0.19: testes estruturais concluídos.\n";
