<?php
declare(strict_types=1);
// Carregado apenas pelo roteador administrativo após CSRF, login e ACL de página.
require_once __DIR__.'/../elections/service.php';
require_once __DIR__.'/../elections/repository.php';
require_ability('manage_settings',$me);
if($action==='toggle_elections_module'){
    $enabled=(string)($_POST['enabled']??'');
    if(!in_array($enabled,['0','1'],true))throw new RuntimeException('Opção inválida.');
    $q=$db->prepare("INSERT INTO settings (name,value) VALUES ('elections_enabled',?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
    $q->execute([$enabled]);flash($enabled==='1'?'Módulo eleitoral ativado.':'Módulo eleitoral desativado sem perder cadastros.');redirect('/admin.php?action=elections');
}
if(setting('elections_enabled','0')!=='1')throw new RuntimeException('Ative o módulo antes de alterar eleições.');
if($action==='save_election'){
    $id=meu_election_save($db,$_POST,max(0,(int)($_POST['id']??0)));
    flash('Cadastro eleitoral salvo. Ainda não há resultados publicados.');redirect('/admin.php?action=elections&id='.$id.'#election-form');
}
if($action==='archive_election'){
    $id=max(0,(int)($_POST['id']??0));
    if(!meu_election_find($db,$id))throw new RuntimeException('Eleição não encontrada.');
    $status=(string)($_POST['archive']??'')==='1'?'archived':'draft';
    $db->prepare('UPDATE elections SET status=? WHERE id=?')->execute([$status,$id]);
    flash($status==='archived'?'Eleição arquivada, sem excluir dados.':'Eleição reaberta.');redirect('/admin.php?action=elections&id='.$id);
}
if($action==='save_election_candidate'){
    $electionId=max(0,(int)($_POST['election_id']??0));
    $candidateId=max(0,(int)($_POST['id']??0));
    try {meu_candidate_save($db,$electionId,$_POST,$candidateId);}
    catch(PDOException $e){if($e->getCode()==='23000')throw new RuntimeException('Já existe uma candidatura com este número nesta eleição.');throw $e;}
    flash('Candidatura cadastrada. Dados ainda não verificados no TSE.');redirect('/admin.php?action=elections&id='.$electionId.'#candidates');
}
if($action==='toggle_election_candidate'){
    $electionId=max(0,(int)($_POST['election_id']??0));$candidateId=max(0,(int)($_POST['id']??0));
    if(!meu_election_find($db,$electionId)||$candidateId===0)throw new RuntimeException('Candidatura ou eleição inválida.');
    $withdraw=(string)($_POST['withdraw']??'');
    if(!in_array($withdraw,['0','1'],true))throw new RuntimeException('Opção inválida.');
    $q=$db->prepare('UPDATE election_candidates SET status=? WHERE id=? AND election_id=?');
    $q->execute([$withdraw==='1'?'withdrawn':'listed',$candidateId,$electionId]);
    if($q->rowCount()===0)throw new RuntimeException('Candidatura não encontrada nesta eleição.');
    flash('Estado da candidatura atualizado.');redirect('/admin.php?action=elections&id='.$electionId.'#candidates');
}
