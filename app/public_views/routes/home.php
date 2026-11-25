<?php 
  $home=igua_home_news($db,$categories,igua_home_preferences($categories,setting('home_category_ids',''),setting('home_region_category','')));
  $featured=$home['featured'];$recent=$home['recent'];$popular=$home['popular'];
  ?>
  <div class="hero-grid home-hero-single">
    <?php if ($featured): ?><a class="hero-card" href="<?= h(news_link($featured)) ?>"><img class="hero-image" loading="eager" decoding="async" fetchpriority="high" data-image-fallback="/assets/img/cidade.jpg" src="<?= h(asset_image($featured['image'])) ?>" alt=""><div class="hero-overlay"><span class="category-label"><?= h($featured['category']??'Cidade') ?></span><h1><?= h($featured['title']) ?></h1><p><?= h($featured['summary']) ?></p><span class="btn primary">Ler mais&nbsp; →</span></div></a>
    <?php else: ?><div class="hero-card empty-hero"><div class="hero-overlay"><span class="category-label"><?= h(igua_brand_name()) ?></span><h1>Acompanhe as notícias de Iguatama e região.</h1><p>Leia as últimas notícias e fique por dentro dos acontecimentos.</p><a href="<?= h(url(['p'=>'buscar'])) ?>" class="btn primary">Ver notícias →</a></div></div><?php endif; ?>

  </div>
  <?php ad_display_slot($db,'p01'); ?>
  <?php
    require_once __DIR__.'/../../bus_home.php';
    $homeBuses=bus_home_summary($db,setting('city','Iguatama - MG'),1);
    $busNow=new DateTimeImmutable('now',new DateTimeZone('America/Sao_Paulo'));
  ?>
  <section class="home-bus" aria-labelledby="home-bus-heading">
    <div class="home-bus-heading">
      <h2 id="home-bus-heading">🚌 Próximo ônibus</h2>
      <a class="home-bus-all" href="/index.php?p=onibus">Ver horários <span aria-hidden="true">→</span></a>
    </div>
    <?php if ($homeBuses['status']==='ok' && !empty($homeBuses['trips'])): $trip=$homeBuses['trips'][0]; ?>
      <a class="home-bus-departure" href="<?=h(url(['p'=>'onibus','origem'=>$homeBuses['origin'],'destino'=>$trip['destination_city']]))?>">
        <span class="home-bus-clock"><strong><?=h($trip['departs_at']->format('H:i'))?></strong><small><?=h(bus_calendar_label($trip['departs_at'],$busNow))?></small></span>
        <span class="home-bus-route"><strong><?=h($trip['destination_city'])?></strong></span>
        <span class="home-bus-remaining" data-bus-time="<?=h($trip['departs_at']->format('c'))?>"><?=h(bus_relative_departure($trip['departs_at'],$busNow))?></span>
      </a>
    <?php else: ?>
      <p class="home-bus-empty"><?php
        echo h(match ($homeBuses['status']) {
          'unavailable'=>'Horários indisponíveis no momento.',
          'no_departures'=>'Sem próximas partidas programadas.',
          default=>'Nenhuma partida cadastrada para esta cidade.'
        });
      ?></p>
    <?php endif; ?>
  </section>
  <?php if ($recent): ?><div class="section-heading headline-heading"><h2>Últimas notícias</h2><a href="<?= h(url(['p'=>'buscar'])) ?>">Ver todas →</a></div>
  <section class="cards-grid"><?php foreach ($recent as $a) news_card($a); ?></section><?php endif; ?>
  <?php ad_display_slot($db,'p02'); ?>
  <?php if ($home['sections']): ?><div class="section-columns home-editorial-sections"><?php foreach ($home['sections'] as $section) section_category($section['category'],$section['rows'],$section['title']); ?></div><?php endif; ?>
  <?php if ($popular): ?>
    <aside class="popular-box home-popular"><div class="box-heading"><h2><span>🔥</span> Mais lidas</h2><a href="<?= h(url(['p'=>'buscar'])) ?>">Ver todas →</a></div><p class="muted home-popular-note">Outras notícias populares que ainda não apareceram nesta página.</p><?php foreach ($popular as $i=>$p): ?><a class="popular-item" href="<?= h(news_link($p)) ?>"><span class="rank"><?= $i+1 ?></span><span><strong><?= h($p['title']) ?></strong><small>◷ <?= h(readable_date($p['published_at'])) ?></small></span></a><?php endforeach; ?></aside>
  <?php endif; ?>
  <?php ad_display_slot($db,'p05'); ?>
