<?php
/** Menu "Aplicativos e Páginas Legais" dentro da administração existente. */
if($action==='legal_apps'):
    $apps=$db->query("SELECT a.*, (SELECT COUNT(*) FROM legal_app_pages p WHERE p.app_id=a.id) AS total_pages FROM legal_apps a ORDER BY a.updated_at DESC,a.id DESC")->fetchAll();
?>
<section class="panel legal-admin">
  <div class="legal-admin-head"><div><h2>Aplicativos cadastrados</h2><p class="muted">Apresentação, privacidade, termos e páginas adicionais.</p></div><a class="btn primary" href="<?=h(admin_link('legal_app_edit'))?>">+ Novo aplicativo</a></div>
  <?php if(!$apps): ?><p class="muted">Nenhum aplicativo cadastrado.</p><?php endif; ?>
  <div class="legal-app-list">
  <?php foreach($apps as $a): ?>
    <article class="legal-app-card">
      <div class="legal-app-card-top">
        <?php if($a['logo_filename']!==''): ?><img alt="" class="legal-app-thumb" src="<?=h(asset_image((string)$a['logo_filename']))?>"><?php endif; ?>
        <div class="legal-app-card-title"><strong><?=h($a['name'])?></strong><small>/<?=h($a['slug'])?></small></div>
        <span class="legal-badge <?= $a['status']==='published'?'is-live':'is-draft' ?>"><?= $a['status']==='published'?'Publicado':'Rascunho' ?></span>
      </div>
      <?php if($a['summary']!==''): ?><p><?=h($a['summary'])?></p><?php endif; ?>
      <small class="muted"><?= (int)$a['total_pages'] ?> páginas · Alterado <?=h(date('d/m/Y H:i',strtotime((string)$a['updated_at'])))?></small>
      <div class="legal-card-actions"><a class="btn outline" href="<?=h(admin_link('legal_app_edit'))?>&amp;id=<?=(int)$a['id']?>">Gerenciar páginas</a>
      <?php if($a['status']==='published'): ?><a class="btn outline" href="<?=h(igua_app_page_url((string)$a['slug']))?>" target="_blank" rel="noopener">Visualizar</a><?php endif; ?>
      <button type="button" class="btn outline" data-legal-copy="<?=h('https://iguatama.online'.igua_app_page_url((string)$a['slug']))?>">Copiar link</button></div>
    </article>
  <?php endforeach; ?>
  </div>
</section>
<?php elseif($action==='legal_app_edit'):
    $id=max(0,(int)($_GET['id']??0));$app=$id?igua_app_find($db,$id):null;
    if($id&&!$app){http_response_code(404);echo '<div class="panel">Aplicativo não encontrado.</div>';return;}
    $pages=[];
    if($app){$q=$db->prepare("SELECT p.id,p.kind,p.slug,p.draft_title,p.status,p.updated_at,p.published_revision_id FROM legal_app_pages p WHERE p.app_id=? ORDER BY CASE p.slug WHEN 'inicio' THEN 0 WHEN 'privacidade' THEN 1 WHEN 'termos' THEN 2 ELSE 3 END,p.id");$q->execute([$id]);$pages=$q->fetchAll();}
?>
<div class="legal-admin">
  <p><a href="<?=h(admin_link('legal_apps'))?>">← Todos os aplicativos</a></p>
  <section class="panel"><div class="legal-admin-head"><h2><?= $app?'Dados do aplicativo':'Novo aplicativo' ?></h2><?php if($app): ?><span class="legal-badge <?= $app['status']==='published'?'is-live':'is-draft' ?>"><?= $app['status']==='published'?'Publicado':'Rascunho' ?></span><?php endif; ?></div>
    <form method="post" action="<?=h(admin_link('save_legal_app'))?>" enctype="multipart/form-data" class="legal-form">
      <?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>">
      <div class="legal-fields-two"><label>Nome do aplicativo *<input name="name" maxlength="160" required value="<?=h($app['name']??'')?>" placeholder="Ex.: Uai Cortes"></label>
      <label>Identificador da URL *<input name="slug" required maxlength="90" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?=h($app['slug']??'')?>" placeholder="ex.: meu-aplicativo"><small>Não muda quando o nome é alterado.</small></label></div>
      <?php if($app): ?><label class="legal-check"><input type="checkbox" name="confirm_slug_change" value="1"> Confirmo o redirecionamento permanente se alterar o identificador</label><?php endif; ?>
      <label>Descrição resumida<textarea rows="2" name="summary" maxlength="300" placeholder="Uma frase sobre o aplicativo"><?=h($app['summary']??'')?></textarea></label>
      <label>Descrição completa<textarea rows="4" name="description" maxlength="24000" placeholder="Recursos e finalidade do aplicativo"><?=h($app['description']??'')?></textarea></label>
      <div class="legal-fields-two"><label>Responsável / desenvolvedor<input name="developer_name" maxlength="180" value="<?=h($app['developer_name']??'') ?>"></label>
      <label>E-mail de suporte<input type="email" name="support_email" maxlength="190" value="<?=h($app['support_email']??'') ?>" placeholder="contato@exemplo.com"></label></div>
      <label>GitHub ou página oficial (HTTPS)<input type="url" name="website_url" maxlength="600" value="<?=h($app['website_url']??'') ?>" placeholder="https://..."></label>
      <label>Logotipo (opcional)<input type="file" name="logo" accept="image/jpeg,image/png,image/webp"></label>
      <?php if($app && $app['logo_filename']!==''): ?><div class="legal-logo-existing"><img src="<?=h(asset_image($app['logo_filename']))?>" alt="Logotipo atual"><label class="legal-check"><input type="checkbox" name="remove_logo" value="1"> Remover logotipo</label></div><?php endif; ?>
      <div class="legal-action-row"><button class="btn primary" type="submit">Salvar aplicativo</button></div>
    </form>
  </section>
  <?php if($app): ?>
  <?php if(($app['slug']??'')==='uaicortes' && ($app['status']??'')==='draft'): ?>
  <section class="panel legal-example-panel"><h2>Modelos prontos para revisão</h2>
    <p class="muted">Apresentação, privacidade e termos baseados no projeto Uai Cortes. Só preenche rascunhos intactos; nunca substitui conteúdo editado ou publicado.</p>
    <form method="post" action="<?=h(admin_link('load_uai_legal_examples'))?>">
      <?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="confirm" value="1">
      <button type="submit" class="btn outline" data-confirm="Preencher apenas páginas não editadas com modelos para revisar?">Carregar exemplos do Uai Cortes</button>
    </form>
  </section>
  <?php endif; ?>
  <section class="panel"><div class="legal-admin-head"><div><h2>Páginas públicas</h2><p class="muted">Cada página possui rascunho e conteúdo público independentes.</p></div></div>
    <div class="legal-pages-list">
    <?php foreach($pages as $pg): $link=igua_app_page_url((string)$app['slug'],(string)$pg['slug']); ?>
      <article class="legal-page-row"><div><strong><?=h($pg['slug']==='inicio'?'Apresentação':($pg['slug']==='privacidade'?'Política de Privacidade':($pg['slug']==='termos'?'Termos de Serviço':$pg['draft_title'])))?></strong><small><?=h($link)?></small></div>
        <span class="legal-badge <?= $pg['status']==='published'?'is-live':'is-draft' ?>"><?= $pg['status']==='published'?'Publicada':'Rascunho' ?></span>
        <div class="legal-page-actions"><a class="btn outline" href="<?=h(admin_link('legal_page_edit'))?>&amp;id=<?=(int)$pg['id']?>">Editar</a>
          <?php if($pg['status']==='published' && $app['status']==='published'): ?>
            <a class="btn outline" href="<?=h($link)?>" target="_blank" rel="noopener noreferrer" aria-label="Ver página publicada: <?=h($pg['draft_title'])?>">Ver página</a>
          <?php else: ?>
            <a class="btn outline" href="<?=h(admin_link('legal_page_edit'))?>&amp;id=<?=(int)$pg['id']?>&amp;legal_preview=1" target="_blank" rel="noopener noreferrer" title="Prévia privada; esta página ainda não está disponível ao público">Prévia</a>
          <?php endif; ?>
          <button type="button" class="btn outline" data-legal-copy="<?=h('https://iguatama.online'.$link)?>">Copiar URL</button></div></article>
    <?php endforeach; ?>
    </div>
    <details class="legal-add-page"><summary>+ Adicionar página informativa</summary><form method="post" action="<?=h(admin_link('create_legal_page'))?>" class="legal-form">
        <?=csrf_field()?><input type="hidden" name="app_id" value="<?=$id?>">
        <div class="legal-fields-two"><label>Título<input name="title" required maxlength="180" placeholder="Perguntas frequentes"></label>
        <label>Slug<input name="slug" required maxlength="90" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="perguntas-frequentes"></label></div><button class="btn primary">Criar rascunho</button></form></details>
  </section>
  <section class="panel"><h2>Publicação do aplicativo</h2><p class="muted">Para publicar, informe responsável e suporte, revise e publique Apresentação e Política de Privacidade.</p>
    <div class="legal-action-row">
      <form method="post" action="<?=h(admin_link('publish_legal_app'))?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="confirm" value="1"><input type="hidden" name="status" value="<?=$app['status']==='published'?'draft':'published'?>"><button class="btn <?= $app['status']==='published'?'outline':'primary' ?>" data-confirm="<?=h($app['status']==='published'?'Despublicar o aplicativo e todas as suas páginas?':'Publicar o aplicativo e as páginas legais que já estão publicadas?')?>"><?= $app['status']==='published'?'Despublicar aplicativo':'Publicar aplicativo' ?></button></form>
      <?php if($app['status']==='published'): ?><a class="btn outline" target="_blank" rel="noopener" href="<?=h(igua_app_page_url((string)$app['slug']))?>">Abrir página pública</a><?php endif; ?>
    </div>
  </section>
  <details class="panel legal-danger-zone"><summary>Excluir aplicativo</summary><p class="muted">A exclusão elimina todas as páginas, links e revisões. Faça backup antes.</p><form method="post" action="<?=h(admin_link('delete_legal_app'))?>" class="legal-form"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><label>Digite <?=h($app['slug'])?> para confirmar<input name="confirm_slug" required autocomplete="off"></label><button class="btn outline" data-confirm="Excluir definitivamente este aplicativo, incluindo revisões?">Excluir permanentemente</button></form></details>
  <?php endif; ?>
</div>
<?php elseif($action==='legal_page_edit'):
    $id=(int)($_GET['id']??0);$page=igua_app_find_page($db,$id);
    if(!$page){http_response_code(404);echo '<div class="panel">Página não encontrada.</div>';return;}
    $getPublished=$db->prepare('SELECT title,body,seo_description,created_at,revision_number FROM legal_app_revisions WHERE id=? AND page_id=?');
    $getPublished->execute([(int)($page['published_revision_id']??0),$id]);$published=$getPublished->fetch()?:null;
    $q=$db->prepare('SELECT r.id,r.revision_number,r.title,r.created_at,u.name AS author FROM legal_app_revisions r LEFT JOIN users u ON u.id=r.created_by WHERE r.page_id=? ORDER BY r.revision_number DESC LIMIT 40');$q->execute([$id]);$revisions=$q->fetchAll();
    $publicLink=igua_app_page_url((string)$page['app_slug'],(string)$page['slug']);
?>
<div class="legal-admin legal-page-editor">
  <p><a href="<?=h(admin_link('legal_app_edit'))?>&amp;id=<?=(int)$page['app_id']?>">← <?=h($page['app_name'])?></a></p>
  <section class="panel">
    <div class="legal-admin-head"><div><h2><?=h($page['draft_title'])?></h2><p class="muted"><?=h($publicLink)?></p></div><span class="legal-badge <?=$page['status']==='published'?'is-live':'is-draft'?>"><?=$page['status']==='published'?'Publicada':'Rascunho'?></span></div>
    <form method="post" action="<?=h(admin_link('save_legal_page'))?>" class="legal-form" id="legal-page-edit-form">
      <?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>">
      <label>Título<input name="title" required maxlength="180" value="<?=h($page['draft_title'])?>"></label>
      <label>Conteúdo (Markdown seguro)<textarea name="body" id="legal-body" rows="15" maxlength="65000" spellcheck="true" placeholder="Escreva o texto aqui..."><?=h($page['draft_body'])?></textarea></label>
      <div class="legal-editor-toolbar" role="toolbar" aria-label="Formatação do texto"><button type="button" data-legal-insert="heading">Título</button><button type="button" data-legal-insert="bold">Negrito</button><button type="button" data-legal-insert="list">Lista</button><button type="button" data-legal-insert="link">Link</button></div>
      <p class="muted">Aceita títulos (#, ##), listas (-), negrito (**texto**) e links HTTPS ([nome](https://...)). HTML inserido será mostrado como texto, não executado.</p>
      <label>Descrição SEO<textarea name="seo_description" rows="2" maxlength="240"><?=h($page['draft_seo_description'])?></textarea></label>
      <div class="legal-action-row"><button class="btn primary" type="submit">Salvar rascunho</button><button class="btn outline" type="button" data-legal-preview-open>Pré-visualizar</button>
      <?php if($page['status']==='published' && $page['app_status']==='published'): ?><a class="btn outline" href="<?=h($publicLink)?>" target="_blank" rel="noopener">Ver página</a><?php endif; ?></div>
    </form>
    <p class="muted">Rascunho atualizado: <?=h(date('d/m/Y H:i',strtotime($page['updated_at'])))?>. <?php if($published): ?>A versão pública é a revisão <?= (int)$published['revision_number'] ?>, de <?=h(date('d/m/Y',strtotime($published['created_at'])))?>.<?php else: ?>Nenhuma versão publicada.<?php endif; ?></p>
  </section>
  <section class="panel"><h2>Publicação</h2><p class="muted">Salve as alterações primeiro. A publicação cria uma revisão permanente; editar o rascunho não muda o conteúdo público.</p>
    <div class="legal-action-row"><form method="post" action="<?=h(admin_link('publish_legal_page'))?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="confirm" value="1"><button class="btn primary" data-confirm="Publicar o último rascunho salvo e criar uma revisão?">Publicar revisão salva</button></form>
    <?php if($page['status']==='published'): ?><form method="post" action="<?=h(admin_link('unpublish_legal_page'))?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="confirm" value="1"><button class="btn outline" data-confirm="Retirar esta página do acesso público? O histórico será mantido.">Despublicar</button></form><?php endif; ?></div>
    <div class="legal-action-row"><button type="button" class="btn outline" data-legal-copy="<?=h('https://iguatama.online'.$publicLink)?>">Copiar URL permanente</button></div>
  </section>
  <section class="panel"><h2>Histórico de versões</h2>
    <?php if(!$revisions): ?><p class="muted">A primeira revisão será criada na publicação.</p><?php endif; ?>
    <?php foreach($revisions as $r): ?><div class="legal-revision-row"><div><strong>Revisão <?= (int)$r['revision_number'] ?></strong><small><?=h(date('d/m/Y H:i',strtotime($r['created_at'])))?> · <?=h($r['author']??'Conta removida')?><?= (int)$page['published_revision_id']===(int)$r['id']?' · Pública':'' ?></small></div>
    <form method="post" action="<?=h(admin_link('restore_legal_revision'))?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="revision_id" value="<?=(int)$r['id']?>"><input type="hidden" name="confirm" value="1"><button class="btn outline" data-confirm="Restaurar esta revisão no RASCUNHO? A página pública não mudará até uma nova publicação.">Restaurar para rascunho</button></form></div><?php endforeach; ?>
  </section>
  <?php if($page['kind']==='custom'): ?><details class="panel legal-danger-zone"><summary>Excluir página adicional</summary><form method="post" action="<?=h(admin_link('delete_legal_page'))?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="confirm" value="1"><button class="btn outline" data-confirm="Excluir esta página e todo o histórico permanentemente?">Excluir página</button></form></details><?php endif; ?>
</div>
<dialog id="legal-preview-dialog" class="igua-modal igua-modal--medium" aria-labelledby="legal-preview-title"><div class="legal-modal-header"><h2 id="legal-preview-title">Prévia do rascunho</h2><button type="button" class="btn outline" data-legal-preview-close aria-label="Fechar">×</button></div><div class="legal-preview-content"><h2 id="legal-preview-heading"><?=h($page['draft_title'])?></h2><div class="legal-app-prose" id="legal-preview-content"><?=igua_app_render_markdown((string)$page['draft_body'])?></div></div><div class="legal-action-row legal-modal-footer"><button class="btn primary" type="button" data-legal-preview-close>Fechar</button></div></dialog>
<?php endif; ?>
<script src="/assets/js/legal-apps-admin.js?v=<?=h(igua_updater_version())?>" defer></script>
