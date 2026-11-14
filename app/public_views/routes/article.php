<?php
// Consultas leves, executadas somente quando a matéria individual é exibida.
$articleStructured = article_blocks($db, (int)$article['id']);
$articleReadingText = $articleStructured
    ? blocks_plain_text($articleStructured)
    : (string)($article['body'] ?? '');
$articleReadingText = strip_tags($articleReadingText);
$articleWordCount = preg_match_all('/[\p{L}\p{N}]+/u', $articleReadingText);
$articleReadingMinutes = max(1, (int)ceil(($articleWordCount === false ? 0 : $articleWordCount) / 200));
$articleRelated = [];
if ((int)($article['category_id'] ?? 0) > 0) {
    try {
        $articleRelated = fetch_news($db, 3, (int)$article['category_id'], (int)$article['id']);
    } catch (PDOException $e) {
        // Uma falha de sugestões nunca deve impedir a leitura da notícia.
        igua_health_log('NEWS_RELATED_UNAVAILABLE', $e);
    }
}
?>
  <div class="breadcrumb"><a href="/index.php">Início</a> › <a href="<?= h(url(['p'=>'categoria','slug'=>$article['category_slug']])) ?>"><?= h($article['category']??'Notícias') ?></a></div>
  <article class="article-view"><span class="category-label"><?= h($article['category']??'Geral') ?></span><h1><?= h($article['title']) ?></h1><p class="article-summary"><?= h($article['summary']) ?></p><div class="article-byline" aria-label="Informações da notícia">
    <span>Por <strong><?= h($article['author'] ?? 'Redação '.igua_brand_name()) ?></strong></span>
    <span><time datetime="<?= h(date('c', strtotime($article['published_at']))) ?>"><?= h(date('d/m/Y \à\s H:i', strtotime($article['published_at']))) ?></time></span>
    <span><?= (int)$article['views'] + 1 ?> visualizações</span>
    <span class="article-reading-time" aria-label="Tempo de leitura estimado: <?= $articleReadingMinutes ?> <?= $articleReadingMinutes === 1 ? 'minuto' : 'minutos' ?>">◷ <?= $articleReadingMinutes ?> min de leitura</span>
  </div>
  <div class="article-share" data-public-share data-share-url="<?= h(news_link($article)) ?>" data-share-title="<?= h($article['title']) ?>" aria-label="Compartilhar esta notícia">
    <button type="button" data-share-trigger>↗ Compartilhar</button>
    <button type="button" data-share-copy>🔗 Copiar link</button>
    <span class="article-share-status" data-share-status role="status" aria-live="polite"></span>
    <input class="article-share-manual" type="text" data-share-manual aria-label="Link da notícia para copiar manualmente" readonly hidden>
  </div>
  <?php $extraCategories=[];try{$xc=$db->prepare('SELECT c.name,c.slug FROM article_categories ac JOIN categories c ON c.id=ac.category_id WHERE ac.article_id=? AND c.id<>? ORDER BY c.name');$xc->execute([(int)$article['id'],(int)$article['category_id']]);$extraCategories=$xc->fetchAll();}catch(PDOException){} ?>
  <?php if($extraCategories): ?><div class="news-extra-cats"><strong>Também em:</strong><?php foreach($extraCategories as $xc): ?><a href="<?= h(url(['p'=>'categoria','slug'=>$xc['slug']])) ?>"><?= h($xc['name']) ?></a><?php endforeach; ?></div><?php endif; ?>
  <?php if ($article['is_demo']): ?><div class="alert article-note"><?php if(($article['source_name']??'')==='Exemplo fictício para aprendizado'): ?><strong>EXEMPLO FICTÍCIO.</strong> Os lugares, situações e detalhes desta matéria são inventados para ensinar o editor e não descrevem acontecimentos reais.<?php else: ?><strong>Acervo de referência — matéria demonstrativa.</strong> Este resumo é um exemplo de layout, não é apuração própria sobre Iguatama. Consulte a fonte original e substitua o conteúdo antes de divulgar.<?php endif; ?></div><?php endif; ?>
  <figure class="article-figure"><img loading="eager" decoding="async" fetchpriority="high" data-image-fallback="/assets/img/cidade.jpg" src="<?= h(asset_image($article['image'])) ?>" alt="<?= h(($article['cover_alt']??'') ?: (($article['image_caption']??'') ?: 'Imagem de apoio da notícia')) ?>"><figcaption><?= h(($article['image_caption'] ?? '') ?: 'Imagem de apoio editorial.') ?><?php if(!empty($article['image_credit'])): ?> <span>· <?= h($article['image_credit']) ?></span><?php endif; ?></figcaption></figure>
  <div class="article-body" id="texto-da-noticia"><?php if($articleStructured){news_render_blocks($articleStructured);ad_display_slot($db,'p04');}else ad_article_body($db,$article['body']); ?></div>
  <?php $gallery=article_media($db,(int)$article['id']); $photos=array_values(array_filter($gallery,static fn($m)=>$m['kind']==='image'));$docs=array_values(array_filter($gallery,static fn($m)=>$m['kind']==='attachment')); ?>
  <?php if($photos): ?>
  <section class="news-gallery" aria-labelledby="news-gallery-title">
    <div class="news-gallery-heading">
      <h2 id="news-gallery-title">Galeria de fotos</h2>
      <span class="news-gallery-total"><?= count($photos) ?> <?= count($photos)===1?'foto':'fotos' ?></span>
    </div>
    <div class="news-gallery-grid">
      <?php foreach($photos as $photoIndex=>$photo):
        $photoCaption=trim((string)($photo['caption']??''));
        $photoCredit=trim((string)($photo['credit']??''));
        $photoLabel=$photoCaption!==''?$photoCaption:'Foto '.($photoIndex+1).' da reportagem';
      ?>
      <figure class="news-gallery-item">
        <a class="news-gallery-open" href="<?= h(media_url($photo['filename'])) ?>" target="_blank" rel="noopener noreferrer" data-gallery-photo data-gallery-caption="<?= h($photoLabel) ?>" data-gallery-credit="<?= h($photoCredit) ?>" aria-label="Ampliar foto <?= $photoIndex+1 ?> de <?= count($photos) ?>: <?= h($photoLabel) ?>">
          <img data-image-fallback="/assets/img/cidade.jpg" src="<?= h(news_media_thumbnail($photo['filename'])) ?>" loading="lazy" decoding="async" alt="<?= h($photoLabel) ?>">
          <span class="news-gallery-zoom" aria-hidden="true">⤢</span>
        </a>
        <figcaption>
          <span class="news-gallery-caption"><?= h($photoLabel) ?></span>
          <?php if($photoCredit!==''): ?><span class="news-gallery-credit">Foto: <?= h($photoCredit) ?></span><?php endif; ?>
        </figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </section>
  <dialog class="news-gallery-lightbox" data-news-gallery-dialog aria-label="Visualizador da galeria de fotos">
    <div class="news-gallery-lightbox-inner">
      <div class="news-gallery-lightbox-top">
        <span data-gallery-counter aria-live="polite"></span>
        <button type="button" data-gallery-close aria-label="Fechar galeria" class="news-gallery-close">✕</button>
      </div>
      <div class="news-gallery-stage">
        <button type="button" data-gallery-prev class="news-gallery-nav news-gallery-prev" aria-label="Foto anterior">‹</button>
        <img data-gallery-large alt="" decoding="async">
        <span class="news-gallery-image-status" data-gallery-image-status role="status" aria-live="polite"></span>
        <button type="button" data-gallery-next class="news-gallery-nav news-gallery-next" aria-label="Próxima foto">›</button>
      </div>
      <div class="news-gallery-lightbox-info">
        <div class="news-gallery-lightbox-description"><p data-gallery-caption></p><small data-gallery-credit></small></div>
        <a data-gallery-original href="#" target="_blank" rel="noopener noreferrer" aria-label="Abrir imagem original em outra aba">Abrir original ↗</a>
      </div>
    </div>
  </dialog>
  <?php endif; ?>
  <?php if($docs): ?><section class="news-attachments"><h2>Documentos e anexos</h2><?php foreach($docs as $file): ?><a href="/download.php?id=<?= (int)$file['id'] ?>">📎 <?= h($file['caption']?:$file['original_name']) ?> <small><?= number_format((int)$file['bytes']/1024,1,',','.') ?> KB</small></a><?php endforeach; ?></section><?php endif; ?>
  <?php if(!empty($article['source_url']) && filter_var($article['source_url'],FILTER_VALIDATE_URL) && str_starts_with($article['source_url'],'https://')): ?><div class="article-source"><strong>Fonte de referência:</strong> <a href="<?= h($article['source_url']) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= h($article['source_name'] ?: 'Consultar a fonte') ?> ↗</a><small class="caption-hint">A fonte pode apontar para uma editoria ou matéria específica. Consulte o original para dados detalhados e atualizações.</small></div><?php endif; ?>
  <?php if ($articleRelated): ?>
  <section class="article-related" aria-labelledby="article-related-heading">
    <div class="article-related-header">
      <h2 id="article-related-heading">Leia também</h2>
      <?php if (!empty($article['category_slug'])): ?><a href="<?= h(url(['p'=>'categoria','slug'=>$article['category_slug']])) ?>">Mais de <?= h($article['category'] ?? 'notícias') ?> →</a><?php endif; ?>
    </div>
    <div class="article-related-list">
      <?php foreach ($articleRelated as $related): ?>
        <a class="article-related-item" href="<?= h(news_link($related)) ?>">
          <img src="<?= h(asset_image($related['image'])) ?>" alt="" loading="lazy" decoding="async" data-image-fallback="/assets/img/cidade.jpg">
          <span class="article-related-content"><strong><?= h($related['title']) ?></strong><small><?= h(readable_date($related['published_at'])) ?><?= !empty($related['is_demo']) ? ' · Exemplo' : '' ?></small></span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
  <a class="back-link" href="/index.php">← Voltar à página inicial</a></article>
