<?php
declare(strict_types=1);
require_once __DIR__.'/support/news_editor_view_source.php';
$root=dirname(__DIR__);
function test120(bool $ok,string $msg):void {if(!$ok)throw new RuntimeException($msg); echo "OK: $msg\n";}
$view=igua_news_editor_view_source();$js=file_get_contents($root.'/public/assets/js/news-editor.js');
$css=file_get_contents($root.'/public/assets/css/style.css');
test120(substr_count($view,'data-step="')===9 && substr_count($view,'data-tab="')===9,'nove etapas sincronizadas');
foreach(['title','summary','blocks','photos','credits','attachments','classify','seo','review'] as $step)test120(str_contains($view,'data-step="'.$step.'"'),'etapa existente: '.$step);
test120(str_contains($view,'name="publication_choice"')&&str_contains($view,'value="scheduled"'),'publicação no final');
test120(str_contains($view,'name="photo_zip"')&&str_contains($view,'name="auto_cover"'),'ZIP e capa automática disponíveis');
test120(str_contains($view,'name="gallery_files[]"')&&str_contains($view,'name="attachment_files[]"'),'contrato de mídias preservado');
test120(str_contains($view,'name="seo_description"')&&str_contains($view,'id="news-seo-audit"'),'SEO editável e audível');
test120(str_contains($js,'function updateReview()')&&str_contains($js,'function canLeave(index)'),'revisão dinâmica e validações');
test120(str_contains($js,'changeTab(0)')&&str_contains($js,'steps.forEach((step,i)=>{step.hidden=i!==active'),'navegação apenas de um passo visível');
test120(str_contains($css,'news-wizard-page')&&str_contains($css,'news-publish-choice'),'layout dedicado do wizard');
