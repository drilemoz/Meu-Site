<?php
declare(strict_types=1);
require_once __DIR__.'/support/news_editor_view_source.php';
$root=dirname(__DIR__);
$js=file_get_contents($root.'/public/assets/js/news-editor.js');
$view=igua_news_editor_view_source();
function check123(bool $ok,string $message):void{if(!$ok)throw new RuntimeException('FALHOU: '.$message);echo "OK: $message\n";}
check123(version_compare(trim(file_get_contents($root.'/VERSION')),'1.0.23','>='),'Versão 1.0.23 ou superior identificada');
check123(substr_count($view,'data-tab="')===3&&substr_count($view,'data-step="')===3,'Mantidas exatamente três etapas de edição');
check123(str_contains($js,'function recoverableDraftName(name)')&&str_contains($js,"!['csrf','id','blocks_json'].includes(name)"),'Snapshot nunca restaura ID, CSRF ou blocos serializados');
check123(str_contains($js,"'new_image_caption[]'")&&str_contains($js,"'new_attachment_credit[]'"),'Metadados temporários de arquivos locais excluídos do autosave');
check123(str_contains($js,"el.checked=values.includes(String(el.value))"),'Checkboxes não selecionados retornam para estado desmarcado');
check123(str_contains($js,'Array.isArray(stored)')&&str_contains($js,'els.forEach((el,i)=>'),'Campos repetidos recuperados por índice');
check123(str_contains($view,'Fotos e anexos locais não fazem parte do rascunho automático.'),'Aviso explícito para evitar falsas expectativas');
