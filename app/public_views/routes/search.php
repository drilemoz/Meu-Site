<?php  ?>
  <div class="archive-head"><span><?= h(igua_brand_name()) ?> / NOTÍCIAS</span><h1><?= $search!==''?'Resultados para “'.h($search).'”':'Todas as notícias' ?></h1><p>Informação organizada para acompanhar o que acontece perto de você.</p></div>
  <form class="archive-filters" action="/index.php" method="get" aria-label="Pesquisar e filtrar notícias">
    <input type="hidden" name="p" value="buscar">
    <label class="archive-filter-search">Palavras-chave<input type="search" name="q" maxlength="100" placeholder="Título ou resumo" value="<?= h($search) ?>"></label>
    <label>Editoria<select name="cat"><option value="">Todas as editorias</option><?php foreach($categories as $c): ?><option value="<?= h($c['slug']) ?>" <?= $archiveCatSlug===$c['slug']?'selected':'' ?>><?= h($c['name']) ?></option><?php endforeach; ?></select></label>
    <label>Período<select name="period"><option value="" <?= $archiveDays===0?'selected':'' ?>>Todas as datas</option><?php foreach([7,30,90] as $n): ?><option value="<?= $n ?>" <?= $archiveDays===$n?'selected':'' ?>>Últimos <?= $n ?> dias</option><?php endforeach; ?></select></label>
    <button type="submit">Buscar</button><a href="<?= h(url(['p'=>'buscar'])) ?>">Limpar</a>
  </form>
  <p class="archive-results" role="status"><?= number_format($archive['total'],0,',','.') ?> notícia(s) encontrada(s) · Página <?= $archive['page'] ?> de <?= $archive['pages'] ?></p>
  <?php if($archive['rows']): ?><div class="cards-grid archive-cards"><?php foreach($archive['rows'] as $a) news_card($a); ?></div><?php else: ?><p class="empty-text">Nenhuma notícia encontrada com esses filtros.</p><?php endif; ?>
  <?php archive_navigation($archive,'buscar',$search,$archiveCatSlug,$archiveDays); ?>
