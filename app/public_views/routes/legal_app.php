<?php
/** Página legal publicada, renderizada integralmente no servidor. */
$legalLinks=[];
$getLinks=$db->prepare("SELECT p.slug,p.draft_title,r.title FROM legal_app_pages p JOIN legal_app_revisions r ON r.id=p.published_revision_id AND r.page_id=p.id WHERE p.app_id=(SELECT id FROM legal_apps WHERE slug=? AND status='published' LIMIT 1) AND p.status='published' ORDER BY CASE p.slug WHEN 'inicio' THEN 0 WHEN 'privacidade' THEN 1 WHEN 'termos' THEN 2 ELSE 3 END,p.id");
$getLinks->execute([(string)$legalPage['slug']]);
$legalLinks=$getLinks->fetchAll();
?>
<article class="legal-app-public">
  <nav class="legal-app-breadcrumb" aria-label="Navegação do aplicativo"><a href="/index.php">Portal</a><span aria-hidden="true">/</span><a href="<?=h(igua_app_page_url((string)$legalPage['slug']))?>"><?=h($legalPage['name'])?></a><?php if($legalPage['page_slug']!=='inicio'): ?><span aria-hidden="true">/</span><span><?=h($legalPage['title'])?></span><?php endif; ?></nav>
  <header class="legal-app-heading">
    <?php if($legalPage['page_slug']==='inicio' && $legalPage['logo_filename']!==''): ?><img class="legal-app-logo" alt="Logotipo de <?=h($legalPage['name'])?>" src="<?=h(asset_image((string)$legalPage['logo_filename']))?>"><?php endif; ?>
    <div><span class="legal-app-overline"><?=h($legalPage['name'])?> · <?= $legalPage['page_slug']==='inicio'?'Aplicativo':'Informações legais' ?></span><h1><?=h($legalPage['title'])?></h1>
    <?php if($legalPage['page_slug']==='inicio' && trim((string)$legalPage['summary'])!==''): ?><p class="legal-app-summary"><?=h($legalPage['summary'])?></p><?php endif; ?></div>
  </header>
  <?php if($legalPage['page_slug']==='inicio' && trim((string)$legalPage['description'])!==''): ?><p class="legal-app-description"><?=nl2br(h((string)$legalPage['description']))?></p><?php endif; ?>
  <div class="legal-app-prose"><?=igua_app_render_markdown((string)$legalPage['body'])?></div>
  <aside class="legal-app-meta">
    <p><strong>Última atualização publicada:</strong> <time datetime="<?=h(substr((string)$legalPage['published_updated_at'],0,10))?>"><?=h(date('d/m/Y',strtotime((string)$legalPage['published_updated_at'])))?></time></p>
    <?php if(trim((string)$legalPage['developer_name'])!==''): ?><p><strong>Responsável:</strong> <?=h($legalPage['developer_name'])?></p><?php endif; ?>
    <?php if($legalPage['support_email']!==''): ?><p><strong>Suporte:</strong> <a href="mailto:<?=h($legalPage['support_email'])?>"><?=h($legalPage['support_email'])?></a></p><?php endif; ?>
    <?php if($legalPage['website_url']!==''): ?><p><a href="<?=h($legalPage['website_url'])?>" rel="noopener noreferrer">Site ou repositório oficial do aplicativo</a></p><?php endif; ?>
  </aside>
  <nav class="legal-app-links" aria-label="Páginas do aplicativo">
    <?php foreach($legalLinks as $item): ?><a <?= $item['slug']===$legalPage['page_slug']?'aria-current="page"':'' ?> href="<?=h(igua_app_page_url((string)$legalPage['slug'],(string)$item['slug']))?>"><?=h($item['slug']==='inicio'?'Apresentação':($item['slug']==='privacidade'?'Política de Privacidade':($item['slug']==='termos'?'Termos de Serviço':$item['title'])))?></a><?php endforeach; ?>
  </nav>
</article>
