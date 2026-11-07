<?php
declare(strict_types=1);
require_once __DIR__.'/support/news_editor_view_source.php';
$root=dirname(__DIR__);
function test120(bool $ok,string $message):void{if(!$ok)throw new RuntimeException('FALHOU: '.$message);echo 'OK: '.$message."\n";}
$view=igua_news_editor_view_source();
$js=file_get_contents($root.'/public/assets/js/news-editor.js');
$css=file_get_contents($root.'/public/assets/css/style.css');
$script=substr($view,strpos($view,'<nav class="news-editor-steps"'));
test120(substr_count($script,'data-tab="')===3,'Etapas exibidas: Básico, Texto e Foto');
test120(substr_count($script,'data-step="')===3,'Somente três seções no formulário');
test120(!str_contains($script,'data-step="seo"')&&!str_contains($script,'4. Confira e salve')&&!str_contains($script,'4. Publicar'),'Tela separada de revisão removida');
test120(!str_contains($script,'news-quick-mode'),'Modo página única removido para navegação guiada');
test120(str_contains($script,'news-step-progress')&&str_contains($script,'Etapa 1 de 3'),'Progresso acessível nas três etapas');
test120(str_contains($js,'steps.forEach((step,i)=>{step.hidden=i!==active')&&str_contains($js,'changeTab(0)'), 'Somente a etapa atual é visível inicialmente');
test120(str_contains($js,"document.querySelector('#news-submit').hidden=active!==steps.length-1"),'Botão Salvar aparece somente após a Foto');
test120(str_contains($js,'if(index===0)')&&str_contains($js,'if(index===1&&!hasText())'), 'Avançar valida dados básicos e conteúdo');
test120(str_contains($script,'news-publish-options')&&str_contains($script,'name="seo_title"')&&str_contains($script,'name="seo_description"'),'SEO editável continua em opções de Básico');
test120(str_contains($js,"if(!form.querySelector('[name=seo_description]').value.trim())seo();"),'Metadescrição continua automática ao salvar');
test120(str_contains($script,'name="featured"')&&str_contains($script,'name="publish_at"'),'Destaque e agendamento continuam disponíveis');
test120(str_contains($script,'name="library_cover"')&&str_contains($script,'name="gallery_files[]"')&&str_contains($script,'name="attachment_files[]"'),'Galeria e anexos preservados');
test120(str_contains($css,'repeat(3,minmax(0,1fr))')&&str_contains($css,'news-editor-footer #news-submit[hidden]'),'Layout mobile para etapas e controles de salvamento');
echo "IguaNews 1.0.20: testes estruturais do editor aprovados.\n";
