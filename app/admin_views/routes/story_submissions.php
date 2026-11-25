<?php
$labels=['new'=>'Novas','accepted'=>'Aceitas','rejected'=>'Rejeitadas','archived'=>'Arquivadas'];
$status=is_string($_GET['status']??null)?$_GET['status']:'new';
if(!isset($labels[$status]) && $status!=='all')$status='new';
$page=max(1,min(50,(int)($_GET['page']??1)));$offset=($page-1)*15;
$where=$status==='all'?'':' WHERE status=?';$args=$status==='all'?[]:[$status];
$q=$db->prepare('SELECT COUNT(*) FROM story_submissions'.$where);$q->execute($args);$total=(int)$q->fetchColumn();
$q=$db->prepare('SELECT id,name,contact,title,message,status,article_id,created_at,photos<>\'[]\' AS has_photos FROM story_submissions'.$where.' ORDER BY id DESC LIMIT 15 OFFSET '.$offset);$q->execute($args);$rows=$q->fetchAll();
?>
<section class="panel"><h2>Caixa de entrada da redação</h2><p class="muted">Sugestões privadas. Confira os fatos e os direitos das imagens antes de publicar.</p>
<div class="form-actions"><?php foreach(['all'=>'Todas']+$labels as $value=>$label):?><a class="btn <?=$status===$value?'primary':'outline'?> small" href="/admin.php?action=story_submissions&amp;status=<?=h($value)?>"><?=h($label)?></a> <?php endforeach;?></div><p><?= $total ?> sugestão(ões)</p></section>
<?php foreach($rows as $row): $id=(int)$row['id']; ?>
<section class="panel" style="overflow-wrap:anywhere"><div class="panel-heading"><h2><?=h($row['title'])?></h2><span><?=h($labels[$row['status']])?></span></div><p class="muted">#<?=$id?> · <?=h($row['created_at'])?></p><p><strong>Remetente:</strong> <?=h($row['name'])?> · <?=h($row['contact'])?></p><p><?=nl2br(h($row['message']))?></p>
<?php if($row['has_photos']): $photoQuery=$db->prepare('SELECT photos FROM story_submissions WHERE id=?');$photoQuery->execute([$id]);$photos=json_decode((string)$photoQuery->fetchColumn(),true);foreach((is_array($photos)?$photos:[]) as $i=>$photo):?><a class="btn outline small" href="/admin.php?action=story_photo&amp;id=<?=$id?>&amp;photo=<?=(int)$i?>">Baixar foto <?=((int)$i+1)?></a> <?php endforeach;unset($photos);endif;?>
<div class="form-actions">
<?php if($row['article_id']):?><a class="btn primary small" href="/admin.php?action=edit&amp;id=<?=(int)$row['article_id']?>">Abrir matéria</a><?php elseif(in_array($row['status'],['new','accepted'],true)):?><form method="post" action="/admin.php?action=story_submissions"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><button class="btn primary small" name="operation" value="draft">Criar rascunho</button></form><?php endif;?>
<form method="post" action="/admin.php?action=story_submissions" class="stacked-form"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><label>Moderação<select name="status"><?php foreach($labels as $value=>$label):?><option value="<?=h($value)?>" <?=$value===$row['status']?'selected':''?>><?=h($label)?></option><?php endforeach;?></select></label><button class="btn outline small" name="operation" value="status">Salvar situação</button></form>
<form method="post" action="/admin.php?action=story_submissions"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><button class="btn outline small" name="operation" value="delete" data-confirm="Excluir definitivamente esta sugestão e suas fotos? A matéria criada será preservada.">Excluir</button></form></div></section>
<?php endforeach;?>
<?php if(!$rows):?><p class="panel">Nenhuma sugestão nesta situação.</p><?php endif;?>
<nav aria-label="Paginação"><?php if($page>1):?><a class="btn outline small" href="/admin.php?action=story_submissions&amp;status=<?=h($status)?>&amp;page=<?=$page-1?>">Anterior</a><?php endif;?> <?php if($offset+15<$total):?><a class="btn outline small" href="/admin.php?action=story_submissions&amp;status=<?=h($status)?>&amp;page=<?=$page+1?>">Próxima</a><?php endif;?></nav>
