<?php
declare(strict_types=1);
require_once __DIR__.'/seo_audit.php';
/** Central de SEO: leitura limitada, sem consultas externas ou modificar publicações. */
function igua_seo_admin_view(PDO $db,array $me): void {
    $status=is_string($_GET['status']??null)?$_GET['status']:'all';
    if(!in_array($status,['all','draft','published'],true))$status='all';
    $where=['1=1'];$params=[];
    if(($me['role']??'')==='author'){$where[]='a.author_id=?';$params[]=(int)$me['id'];}
    if($status!=='all'){$where[]='a.status=?';$params[]=$status;}
    $sql='SELECT a.id,a.author_id,a.title,a.seo_title,a.seo_description,a.slug,a.summary,LEFT(a.body,2000) AS body,a.image,a.image_caption,a.status,a.is_demo,a.published_at, '
        .'m.alt_text AS cover_alt, EXISTS(SELECT 1 FROM article_blocks b WHERE b.article_id=a.id) AS has_blocks '
        .'FROM articles a LEFT JOIN media_library m ON m.filename=a.image AND m.deleted_at IS NULL '
        .'WHERE '.implode(' AND ',$where).' ORDER BY a.id DESC LIMIT 40';
    $stmt=$db->prepare($sql);$stmt->execute($params);$articles=$stmt->fetchAll();
    $base=setting('seo_site_url');try{$base=safe_seo_url($base);}catch(Throwable){$base='';}
    $index=setting('seo_index','0')==='1';$pretty=setting('seo_pretty_urls','0')==='1';
?>
<section class="panel seo-center">
  <div class="panel-heading"><div><h2>Central de SEO</h2><p class="muted">Auditoria editorial das 40 notícias mais recentes deste filtro. Não é nota do Google e não garante indexação.</p></div><div class="seo-card-actions"><a class="btn outline small" href="<?= h(admin_link('settings')) ?>">Configurações</a><a class="btn outline small" href="<?=h(admin_link('sitemap'))?>">Sitemap e Indexação</a></div></div>
  <div class="seo-site-status" aria-label="Configurações da publicação">
    <span class="seo-status <?= $base!==''?'is-ok':'is-warning' ?>"><?= $base!==''?'✓':'!' ?> Domínio HTTPS: <?= $base!==''?h($base):'não configurado' ?></span>
    <span class="seo-status <?= $index?'is-ok':'is-warning' ?>"><?= $index?'✓ Indexação permitida':'! Indexação desligada' ?></span>
    <span class="seo-status"><?= $pretty?'URLs amigáveis ativadas':'URLs clássicas ativas' ?></span>
  </div>
  <p class="muted tiny">Rascunhos, matérias agendadas e conteúdos de demonstração não devem ser indexados. Teste a configuração HTTPS, robots e sitemap na hospedagem antes de liberar a indexação.</p>
  <nav class="seo-filter" aria-label="Filtrar notícias na Central de SEO">
    <?php foreach(['all'=>'Todas','draft'=>'Rascunhos','published'=>'Publicadas/agendadas'] as $k=>$label): ?><a class="btn outline small <?= $status===$k?'seo-filter-active':'' ?>" href="<?= h(admin_link('seo').'&status='.$k) ?>"><?= h($label) ?></a><?php endforeach; ?>
  </nav>
</section>
<section class="seo-article-list" aria-label="Diagnóstico das notícias">
  <?php if(!$articles): ?><div class="panel"><p class="muted">Nenhuma notícia encontrada para este filtro.</p></div><?php endif; ?>
  <?php foreach($articles as $a): $check=igua_seo_audit($a);$isPublic=$a['status']==='published' && strtotime((string)$a['published_at'])<=time() && !(int)$a['is_demo']; ?>
  <article class="panel seo-article-card">
    <div class="seo-article-head"><div><small class="muted"><?= h($a['status']==='draft'?'Rascunho':($isPublic?'Publicada':'Agendada/demonstração')) ?></small><h3><?= h($a['title']) ?></h3><code class="seo-slug"><?= h($a['slug']) ?></code></div><div class="seo-score" aria-label="Pontuação editorial <?= (int)$check['score'] ?> de 100"><strong><?= (int)$check['score'] ?></strong><small>/100</small></div></div>
    <?php if($check['issues']): ?><details class="seo-issues"><summary><?= count($check['issues']) ?> pontos a revisar</summary><ul><?php foreach($check['issues'] as $issue): ?><li><?= h($issue) ?></li><?php endforeach; ?></ul></details><?php else: ?><p class="seo-success">✓ Checklist editorial completo</p><?php endif; ?>
    <div class="seo-card-actions"><a class="btn primary small" href="<?= h(admin_link('edit').'&id='.(int)$a['id']) ?>">Revisar notícia</a><?php if($isPublic): ?><a class="btn outline small" target="_blank" rel="noopener" href="<?= h(url(['p'=>'noticia','slug'=>(string)$a['slug']])) ?>">Ver publicação ↗</a><?php endif; ?></div>
  </article>
  <?php endforeach; ?>
</section>
<?php }
