<?php
// Executado somente após autenticação, autorização e CSRF centralizados.
$id=(int)($_POST['id']??0);$operation=$_POST['operation']??'';
if($id<1)throw new DomainException('Sugestão inválida.');
if($operation==='draft'){
    $article=igua_story_draft($db,$id,(int)$me['id']);flash('Rascunho disponível. Revise antes de publicar.');redirect('/admin.php?action=edit&id='.$article);
}elseif($operation==='status'){
    $status=$_POST['status']??'';
    if(!in_array($status,['new','accepted','rejected','archived'],true))throw new DomainException('Situação inválida.');
    $q=$db->prepare('UPDATE story_submissions SET status=? WHERE id=?');$q->execute([$status,$id]);flash('Situação atualizada.');
}elseif($operation==='delete'){
    $q=$db->prepare('DELETE FROM story_submissions WHERE id=?');$q->execute([$id]);flash('Sugestão e fotos excluídas.');
}else throw new DomainException('Operação inválida.');
redirect('/admin.php?action=story_submissions');
