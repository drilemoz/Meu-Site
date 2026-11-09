<?php
declare(strict_types=1);
/** Verificações offline da galeria pública sem conectar ou alterar banco. */
$root=dirname(__DIR__);
$view=file_get_contents($root.'/app/public_views/routes/article.php');
$layout=file_get_contents($root.'/app/public_views/layout.php');
$css=file_get_contents($root.'/public/assets/css/modules/07-editor-structured.css');
$js=file_get_contents($root.'/public/assets/js/article-gallery.js');
function galleryOk234(bool $ok,string $description):void {static $n=0;$n++;if(!$ok)throw new RuntimeException('Falhou: '.$description);echo "OK {$n}: {$description}\n";}
galleryOk234(str_contains($view,'aria-labelledby="news-gallery-title"') && str_contains($view,'id="news-gallery-title"'),'região rotulada corretamente');
galleryOk234(str_contains($view,'count($photos)'),'contador de fotografias com dados reais');
galleryOk234(str_contains($view,'loading="lazy" decoding="async"'),'miniaturas com carregamento progressivo');
galleryOk234(str_contains($view,'news_media_thumbnail(') && str_contains($view,'media_url($photo[\'filename\'])'),'miniatura separada da imagem original');
galleryOk234(str_contains($view,'data-gallery-photo') && str_contains($view,'target="_blank" rel="noopener noreferrer"'),'links originais permanecem sem JavaScript');
galleryOk234(str_contains($view,'h($photoLabel)') && str_contains($view,'h($photoCredit)'),'legendas/créditos tratados para saída HTML');
galleryOk234(str_contains($view,'data-gallery-caption="<?= h($photoLabel) ?>"'),'legenda escapada em atributo');
galleryOk234(str_contains($view,'data-news-gallery-dialog') && str_contains($view,'aria-label="Visualizador da galeria de fotos"'),'dialog com nome acessível');
galleryOk234(str_contains($view,'data-gallery-image-status') && str_contains($view,'aria-live="polite"'),'mensagem acessível para falha de imagem');
galleryOk234(str_contains($view,'data-gallery-prev') && str_contains($view,'data-gallery-next') && str_contains($view,'data-gallery-close'),'controles de navegação e fechamento');
galleryOk234(str_contains($layout,"\$route==='noticia' && \$article") && str_contains($layout,'article-gallery.js?v='),'JS carregado somente na página da matéria');
galleryOk234(str_contains($js,"typeof dialog.showModal !== 'function'"),'fallback nativo sem dialog');
galleryOk234(str_contains($js,"event.metaKey || event.ctrlKey || event.shiftKey || event.altKey"),'atalhos para abrir imagem original preservados');
galleryOk234(str_contains($js,"image.alt = description") && str_contains($js,"textContent = description"),'alt/legenda sem innerHTML inseguro');
galleryOk234(str_contains($js,"event.key === 'ArrowLeft'") && str_contains($js,"event.key === 'ArrowRight'"),'setas de teclado');
galleryOk234(str_contains($js,"stage.addEventListener('touchstart'") && str_contains($js,"stage.addEventListener('touchend'"),'gesto de deslizar');
galleryOk234(str_contains($js,"dialog.addEventListener('close'") && str_contains($js,'opener.focus()'),'restauração do foco de teclado');
galleryOk234(str_contains($css,'.news-gallery-lightbox::backdrop') && str_contains($css,'.news-gallery-open:focus-visible'),'contraste do modal e foco');
galleryOk234(str_contains($css,'prefers-reduced-motion:reduce') && str_contains($css,'max-width:620px'),'acessibilidade e layout móvel');
galleryOk234(!str_contains($js,'innerHTML') && !str_contains($view,'onclick='),'sem montagem HTML com conteúdo do banco');
