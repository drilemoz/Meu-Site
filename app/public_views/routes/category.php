<?php  ?>
  <div class="archive-head"><span><?= h(igua_brand_name()) ?> / EDITORIAS</span><h1><?= h($category['name']) ?></h1><p>Notícias e atualizações da editoria <?= h($category['name']) ?>.</p></div>
  <form class="archive-filters" action="/index.php" method="get" aria-label="Filtrar notícias da editoria">
    <input type="hidden" name="p" value="categoria"><input type="hidden" name="slug" value="<?= h($category['slug']) ?>">
    <label>Período<select name="period"><option value="" <?= $archiveDays===0?'selected':'' ?>>Todas as datas</option><?php foreach([7,30,90] as $n): ?><option value="<?= $n ?>" <?= $archiveDays===$n?'selected':'' ?>>Últimos <?= $n ?> dias</option><?php endforeach; ?></select></label>
    <button type="submit">Filtrar</button><?php if($archiveDays): ?><a href="<?= h(cat_link($category)) ?>">Limpar</a><?php endif; ?>
  </form>
  <p class="archive-results" role="status"><?= number_format($archive['total'],0,',','.') ?> notícia(s) encontrada(s) · Página <?= $archive['page'] ?> de <?= $archive['pages'] ?></p>
  <?php if($archive['rows']): ?><div class="cards-grid archive-cards"><?php foreach($archive['rows'] as $a) news_card($a); ?></div><?php else: ?><p class="empty-text">Nenhuma notícia publicada neste período.</p><?php endif; ?>
  <?php archive_navigation($archive,'categoria','',(string)$category['slug'],$archiveDays); ?>
