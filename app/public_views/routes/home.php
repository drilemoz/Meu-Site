<?php 
  $featured=fetch_featured_news($db);
  $popular=fetch_popular_news($db);
  $recent=fetch_news($db,8,null,$featured?(int)$featured['id']:null);
  ?>
  <div class="hero-grid">
    <?php if ($featured): ?><a class="hero-card" href="<?= h(news_link($featured)) ?>"><img class="hero-image" data-image-fallback="/assets/img/cidade.jpg" src="<?= h(asset_image($featured['image'])) ?>" alt=""><div class="hero-overlay"><span class="category-label"><?= h($featured['category']??'Cidade') ?></span><h1><?= h($featured['title']) ?></h1><p><?= h($featured['summary']) ?></p><span class="btn primary">Ler mais&nbsp; →</span></div></a>
    <?php else: ?><div class="hero-card empty-hero"><div class="hero-overlay"><span class="category-label"><?= h(igua_brand_name()) ?></span><h1>Acompanhe as notícias de Iguatama e região.</h1><p>Leia as últimas notícias e fique por dentro dos acontecimentos.</p><a href="<?= h(url(['p'=>'buscar'])) ?>" class="btn primary">Ver notícias →</a></div></div><?php endif; ?>
    <aside class="popular-box"><div class="box-heading"><h2><span>🔥</span> Mais lidas</h2><a href="<?= h(url(['p'=>'buscar'])) ?>">Ver todas →</a></div><?php foreach ($popular as $i=>$p): ?><a class="popular-item" href="<?= h(news_link($p)) ?>"><span class="rank"><?= $i+1 ?></span><span><strong><?= h($p['title']) ?></strong><small>◷ <?= h(readable_date($p['published_at'])) ?></small></span></a><?php endforeach; ?><?php if (!$popular): ?><p class="muted">Sem notícias publicadas.</p><?php endif; ?><?php ad_display_slot($db,'p05'); ?></aside>
  </div>
  <?php ad_display_slot($db,'p01'); ?>
  <div class="categories-strip"><a class="chip selected" href="/index.php">Todas</a><?php foreach ($categories as $c): ?><a class="chip" href="<?= h(cat_link($c)) ?>"><span style="color:<?= h($c['color']) ?>"><?= h($c['icon']) ?></span><?= h($c['name']) ?></a><?php endforeach; ?></div>
  <div class="section-heading headline-heading"><h2>Últimas notícias</h2><a href="<?= h(url(['p'=>'buscar'])) ?>">Ver todas →</a></div>
  <?php if ($recent): ?><section class="cards-grid"><?php foreach (array_slice($recent,0,4) as $a) news_card($a); ?></section><?php else: ?><p class="empty-text">Ainda não existem notícias recentes.</p><?php endif; ?>
  <?php ad_display_slot($db,'p02'); ?>
  <div class="section-columns"><?php foreach ($categories as $c) if (in_array($c['slug'],['cidade','politica','eventos'],true)) section_category($db,$c); ?></div>
  <?php if (count($recent)>4): ?><section class="more-news"><div class="section-heading"><h2>Acontece na região</h2><a href="<?= h(url(['p'=>'buscar'])) ?>">Ver mais →</a></div><div class="more-grid"><?php foreach (array_slice($recent,4,4) as $a) mini_card($a); ?></div></section><?php endif; ?>
