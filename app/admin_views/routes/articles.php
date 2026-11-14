<?php require_once __DIR__.'/../partials/news_cards.php'; ?>
<?php 
    $newsSearch=news_admin_search_term($_GET['q']??'');
    $newsStatus=news_admin_status(is_string($_GET['status']??null)?$_GET['status']:'all');
    $newsPage=news_admin_page_number($_GET['page']??1);
    $newsLimit=20;
    [$where,$bindings]=news_admin_conditions($me,$newsStatus,$newsSearch);
    $counter=$db->prepare('SELECT COUNT(*) FROM articles a WHERE '.$where);$counter->execute($bindings);
    $newsTotal=(int)$counter->fetchColumn();$newsPages=max(1,(int)ceil($newsTotal/$newsLimit));$newsPage=min($newsPage,$newsPages);
    $rows=[];
    if ($newsTotal>0) {
        $stm=$db->prepare('SELECT a.id,a.author_id,a.title,a.status,a.is_demo,a.views,a.published_at,a.created_at,c.name AS category FROM articles a LEFT JOIN categories c ON c.id=a.category_id WHERE '.$where.' ORDER BY a.created_at DESC,a.id DESC LIMIT ? OFFSET ?');
        $i=1;foreach($bindings as $binding)$stm->bindValue($i++,$binding,is_int($binding)?PDO::PARAM_INT:PDO::PARAM_STR);
        $stm->bindValue($i++,$newsLimit,PDO::PARAM_INT);$stm->bindValue($i,($newsPage-1)*$newsLimit,PDO::PARAM_INT);
        $stm->execute();$rows=$stm->fetchAll();
    }
    $newsLink=static function(int $page,string $status,string $q):string{return '/admin.php?'.http_build_query(['action'=>'articles','status'=>$status,'q'=>$q,'page'=>$page]);}; ?>
    <div class="panel">
      <div class="panel-heading news-list-heading"><div><h2>Notícias cadastradas</h2><p class="muted tiny"><?= $newsTotal ?> resultado(s)</p></div><div class="news-list-actions"><button type="button" class="btn outline news-search-open" id="news-filter-open" aria-haspopup="dialog" aria-controls="news-filter-dialog" aria-label="Pesquisar e filtrar notícias" title="Pesquisar notícias"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m16 16 5 5"></path></svg><?= ($newsSearch!==''||$newsStatus!=='all')?'<span class="filter-active-dot" aria-label="Filtros ativos"></span>':'' ?></button><a class="btn primary small" href="<?= h(admin_link('new')) ?>">+ Nova notícia</a></div></div>
<dialog id="news-filter-dialog" class="admin-ui-dialog news-filter-dialog" aria-labelledby="news-filter-title"><div class="admin-ui-dialog-head"><h2 id="news-filter-title">Pesquisar notícias</h2><button type="button" class="admin-ui-close" id="news-filter-close" aria-label="Fechar pesquisa">×</button></div>      <form method="get" action="/admin.php" class="news-admin-filter"><input type="hidden" name="action" value="articles"><label>Pesquisar<input type="search" name="q" maxlength="100" value="<?= h($newsSearch) ?>" placeholder="Título ou resumo"></label><label>Situação<select name="status"><?php foreach(['all'=>'Todas','draft'=>'Rascunhos','published'=>'Publicadas','scheduled'=>'Agendadas'] as $filterKey=>$filterLabel): ?><option value="<?= h($filterKey) ?>" <?= selected($newsStatus,$filterKey) ?>><?= h($filterLabel) ?></option><?php endforeach; ?></select></label><button class="btn outline" type="submit">Filtrar</button><a href="/admin.php?action=articles" class="btn outline">Limpar</a></form>
    </dialog>
      <div class="news-articles-table news-card-list" aria-label="Notícias cadastradas">
      <?php foreach($rows as $a): igua_news_admin_card($a,$me,$newsPage,$newsStatus,$newsSearch); endforeach; ?>
      </div>
      <?php if(!$rows): ?><p class="empty-text">Nenhuma notícia encontrada neste filtro.</p><?php endif; ?>
      <?php if($newsPages>1): ?><nav class="news-admin-pagination" aria-label="Páginas de notícias"><?php if($newsPage>1): ?><a href="<?= h($newsLink($newsPage-1,$newsStatus,$newsSearch)) ?>">← Anterior</a><?php endif; ?><span>Página <?= $newsPage ?> de <?= $newsPages ?></span><?php if($newsPage<$newsPages): ?><a href="<?= h($newsLink($newsPage+1,$newsStatus,$newsSearch)) ?>">Próxima →</a><?php endif; ?></nav><?php endif; ?>
    </div>
<?php if(role_allowed($me['role'],'manage_taxonomy')): ?>
<details class="panel admin-collapsible-form news-examples-panel" style="margin-top:20px"><summary>📖 Exemplos do editor simples</summary><p class="muted">Adicione 3 matérias <strong>totalmente fictícias</strong> já preenchidas com cartões e seções expansíveis. Elas aparecerão no portal identificadas como exemplo e ficam fora dos buscadores. Não substitui notícias suas nem o acervo anterior. A ação gera um backup.</p>
<form method="post" action="<?= h(admin_link('load_editor_examples')) ?>" class="stacked-form" style="max-width:560px"><?= csrf_field() ?><label>Confirme sua senha<input type="password" name="confirm_password" required autocomplete="current-password"></label><button class="btn primary" type="submit" data-confirm="Gerar backup e adicionar os exemplos fictícios sem alterar matérias existentes?">Adicionar 3 exemplos preenchidos</button></form></details>
<details class="panel admin-collapsible-form news-examples-panel" style="margin-top:20px"><summary>Acervo jornalístico de referência</summary><p class="muted">Carrega 100 textos de demonstração baseados em pautas de fontes verificáveis, 10 por categoria. As imagens são ilustrações geradas por IA, não fotos dos acontecimentos. O sistema cria um backup antes, troca apenas matérias marcadas como demonstração e preserva suas notícias próprias.</p><form method="post" action="<?= h(admin_link('load_samples')) ?>" class="stacked-form" style="max-width:560px"><?= csrf_field() ?><label>Confirme sua senha<input type="password" name="confirm_password" required autocomplete="current-password"></label><button class="btn primary" type="submit" data-confirm="Gerar backup e substituir somente as notícias de demonstração por 100 textos de referência?">Carregar 100 notícias de amostra →</button></form></details><?php endif; ?>
