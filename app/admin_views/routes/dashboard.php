<?php require_once __DIR__.'/../partials/news_cards.php'; ?>
<?php 
    if($me['role']==='author'){
        $tot=$db->prepare("SELECT COUNT(*) AS n,COALESCE(SUM(status='published'),0) AS published,COALESCE(SUM(views),0) AS views FROM articles WHERE author_id=?");$tot->execute([(int)$me['id']]);$stats=$tot->fetch();
        $count=(int)$stats['n'];$published=(int)$stats['published'];$views=(int)$stats['views'];
    }else{
        $count=(int)$db->query('SELECT COUNT(*) FROM articles')->fetchColumn();
        $published=(int)$db->query("SELECT COUNT(*) FROM articles WHERE status='published'")->fetchColumn();
        $views=(int)$db->query('SELECT COALESCE(SUM(views),0) FROM articles')->fetchColumn();
    }
    $catCount=(int)$db->query('SELECT COUNT(*) FROM categories')->fetchColumn(); ?>
  <div class="stats-grid"><div class="stat"><small>Total de notícias</small><strong><?= $count ?></strong><span>Publicadas e rascunhos</span></div><div class="stat"><small>Publicadas</small><strong><?= $published ?></strong><span>Disponíveis no portal</span></div><div class="stat"><small>Visualizações</small><strong><?= $views ?></strong><span>Contador interno</span></div><div class="stat"><small>Editorias</small><strong><?= $catCount ?></strong><span>Categorias cadastradas</span></div></div>
  <?php if(role_allowed($me['role'],'manage_settings')): ?><div class="panel admin-dashboard-health"><div class="compact-heading"><strong>Saúde do sistema</strong><details class="compact-help"><summary aria-label="Ajuda sobre o diagnóstico de saúde">?</summary><div class="compact-help-content">Confira banco, espaço, backups, SMTP e erros. A verificação é feita sob demanda.</div></details></div><a class="btn outline small" href="/admin.php?action=health">Verificar →</a></div><?php endif; ?>
  <div class="panel dashboard-news-panel">
    <div class="panel-heading"><h2>Publicações recentes</h2><a class="btn primary small" href="<?= h(admin_link('new')) ?>">+ Criar notícia</a></div>
    <?php
    $sql='SELECT a.id,a.author_id,a.title,a.status,a.is_demo,a.views,a.published_at,a.created_at,c.name AS category FROM articles a LEFT JOIN categories c ON c.id=a.category_id';
    if($me['role']==='author'){$stmt=$db->prepare($sql.' WHERE a.author_id=? ORDER BY a.created_at DESC,a.id DESC LIMIT 7');$stmt->execute([(int)$me['id']]);}
    else{$stmt=$db->query($sql.' ORDER BY a.created_at DESC,a.id DESC LIMIT 7');}
    $recent=$stmt->fetchAll(); ?>
    <div class="news-card-list dashboard-news-list" aria-label="Últimas notícias cadastradas">
      <?php foreach($recent as $a): igua_news_admin_card($a,$me); endforeach; ?>
    </div>
    <?php if(!$recent): ?><p class="empty-text">Nenhuma notícia cadastrada.</p><?php endif; ?>
    <a class="dashboard-news-more" href="<?= h(admin_link('articles')) ?>">Ver todas as notícias →</a>
  </div>
