<?php
declare(strict_types=1);
function meu_elections_list(PDO $db): array {
    return $db->query('SELECT e.*, (SELECT COUNT(*) FROM election_candidates c WHERE c.election_id=e.id) AS candidate_count FROM elections e ORDER BY election_year DESC,id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
}
function meu_election_find(PDO $db,int $id): ?array {
    if($id<1)return null;
    $q=$db->prepare('SELECT * FROM elections WHERE id=? LIMIT 1');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}
function meu_candidates_list(PDO $db,int $electionId): array {
    $q=$db->prepare('SELECT * FROM election_candidates WHERE election_id=? ORDER BY candidate_number,ballot_name LIMIT 600');
    $q->execute([$electionId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}
function meu_election_save(PDO $db,array $data,int $id): int {
    $d=meu_election_validate($data);
    $values=[$d['title'],$d['year'],$d['scope'],$d['office'],$d['round'],$d['uf'],$d['city'],$d['ibge'],$d['date']===''?null:$d['date'],$d['source']];
    if($id>0){
        if(!meu_election_find($db,$id))throw new RuntimeException('Eleição não encontrada.');
        $q=$db->prepare('UPDATE elections SET title=?,election_year=?,election_scope=?,office=?,round_number=?,state_code=?,municipality=?,municipality_ibge=?,election_date=?,source_url=? WHERE id=?');
        $q->execute([...$values,$id]);return $id;
    }
    $q=$db->prepare('INSERT INTO elections(title,election_year,election_scope,office,round_number,state_code,municipality,municipality_ibge,election_date,source_url) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $q->execute($values);return (int)$db->lastInsertId();
}
function meu_candidate_save(PDO $db,int $electionId,array $data,int $id): int {
    if(!meu_election_find($db,$electionId))throw new RuntimeException('Eleição não encontrada.');
    $d=meu_candidate_validate($data);
    $values=[$d['full'],$d['ballot'],$d['number'],$d['abbr'],$d['party']];
    if($id>0){
        $q=$db->prepare('UPDATE election_candidates SET full_name=?,ballot_name=?,candidate_number=?,party_abbr=?,party_name=? WHERE id=? AND election_id=?');
        $q->execute([...$values,$id,$electionId]);
        if($q->rowCount()===0){
            $check=$db->prepare('SELECT id FROM election_candidates WHERE id=? AND election_id=?');$check->execute([$id,$electionId]);
            if(!$check->fetchColumn())throw new RuntimeException('Candidato não encontrado nesta eleição.');
        }
        return $id;
    }
    $q=$db->prepare('INSERT INTO election_candidates(election_id,full_name,ballot_name,candidate_number,party_abbr,party_name) VALUES (?,?,?,?,?,?)');
    $q->execute([$electionId,...$values]);return (int)$db->lastInsertId();
}
