<?php
declare(strict_types=1);
require_once __DIR__.'/../app/admin_views/partials/news_cards.php';
function check194(bool $ok,string $info):void {if(!$ok)throw new RuntimeException('1.0.94: '.$info);}
$now=new DateTimeImmutable('2026-11-07 12:00:00',new DateTimeZone('America/Sao_Paulo'));
$sample=['status'=>'published','published_at'=>'2026-11-07 08:00:00','created_at'=>'2026-11-01 08:00:00'];
check194(igua_news_relative_date($sample,$now)==='Hoje','publicado hoje');
$sample['published_at']='2026-11-06 22:00:00';check194(igua_news_relative_date($sample,$now)==='Ontem','publicado ontem');
$sample['published_at']='2026-11-04 09:00:00';check194(igua_news_relative_date($sample,$now)==='Há 3 dias','publicado há três dias');
$sample['published_at']='2026-11-08 09:00:00';check194(igua_news_relative_date($sample,$now)==='Amanhã','agendado amanhã');
$sample['status']='draft';$sample['created_at']='2026-11-03 08:00:00';check194(igua_news_relative_date($sample,$now)==='Criado há 4 dias','rascunho não atribui data publicada');
$sample['created_at']='invalid';check194(igua_news_relative_date($sample,$now)==='Data indisponível','data inválida não é inventada');
$view=file_get_contents(__DIR__.'/../app/news_admin_view/details.php');
check194(str_contains($view,'name="seo_title"')&&str_contains($view,'name="seo_description"')&&str_contains($view,'name="url_slug"'),'campos persistidos mantidos no formulário');
check194(substr_count($view,'data-news-seo-tool=')===3&&str_contains($view,'id="news-seo-dialog-open"'),'quatro ações compactas');
check194(str_contains($view,'id="news-seo-diag-save"')&&str_contains($view,'id="news-seo-tool-save"'),'ações de salvar dentro dos pop-ups');
$header=file_get_contents(__DIR__.'/../app/news_admin_view/header.php');
check194(!str_contains($header,'news-import-optional')&&str_contains($header,'news-editor-top-actions'),'importação removida do wizard e saída no cabeçalho');
$js=file_get_contents(__DIR__.'/../public/assets/js/file-inputs.js');
check194(str_contains($js,"input.closest('.news-upload-tile')"),'seletor geral ignora seletor personalizado');
$admin=file_get_contents(__DIR__.'/../app/admin_views/routes/articles.php');
$dashboard=file_get_contents(__DIR__.'/../app/admin_views/routes/dashboard.php');
check194(str_contains($admin,'igua_news_admin_card(')&&str_contains($dashboard,'igua_news_admin_card('),'cartões reutilizados nas duas telas');
$cards=file_get_contents(__DIR__.'/../app/admin_views/partials/news_cards.php');
check194(str_contains($cards,'news-card-delete')&&str_contains($cards,'<svg')&&str_contains($cards,'csrf_field()')&&str_contains($cards,"'delete'"),'lixeira SVG, CSRF e ACL preservados');
check194(str_contains($cards,'can_edit_article(')&&str_contains($cards,'igua_news_relative_date('),'autorização e datas relativas');
echo "1.0.94: wizard, SEO, uploads e cartões compartilhados aprovados.
";
