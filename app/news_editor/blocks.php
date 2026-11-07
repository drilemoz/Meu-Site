<?php
declare(strict_types=1);
/** IguaNews: serviço editorial, módulo blocks. Requer bootstrap.php no ponto de entrada. */

function normalize_news_blocks(string $raw): array {
    if ($raw==='') return [];
    if (strlen($raw)>180000) throw new RuntimeException('O conteúdo em blocos excede 180 KB.');
    $data=json_decode($raw,true,64,JSON_THROW_ON_ERROR);
    if (!is_array($data)||!array_is_list($data)||count($data)>60) throw new RuntimeException('Lista de blocos inválida (máximo 60).');
    $allowed=['heading','paragraph','fact','section','quote','list'];
    $out=[];
    foreach($data as $entry){
        if(!is_array($entry)||!in_array($entry['kind']??null,$allowed,true))throw new RuntimeException('Tipo de bloco inválido.');
        $h=trim((string)($entry['heading']??''));$content=trim((string)($entry['content']??''));
        if(ulen($h)>180||ulen($content)>6500)throw new RuntimeException('Um dos blocos excedeu o limite.');
        if($h===''&&$content==='') continue;
        $out[]=['kind'=>$entry['kind'],'heading'=>$h,'content'=>$content,'collapsed'=>empty($entry['collapsed'])?0:1];
    }
    return $out;
}

function blocks_plain_text(array $blocks): string {
    return implode("\n\n",array_map(static fn($v)=>trim(($v['heading']!==''?$v['heading']."\n":'').$v['content']),$blocks));
}

function save_article_extras(PDO $db,int $articleId,array $extraCats,array $blocks): void {
    // Executar dentro da mesma transação que salva a notícia.
    $db->prepare('DELETE FROM article_categories WHERE article_id=?')->execute([$articleId]);
    $cat=$db->prepare('INSERT INTO article_categories(article_id,category_id) VALUES (?,?)');
    foreach(array_unique(array_map('intval',$extraCats)) as $cid){
        if($cid<=0)continue;
        $check=$db->prepare('SELECT id FROM categories WHERE id=?');$check->execute([$cid]);
        if(!$check->fetchColumn())throw new RuntimeException('Categoria adicional não existe.');
        $cat->execute([$articleId,$cid]);
    }
    $db->prepare('DELETE FROM article_blocks WHERE article_id=?')->execute([$articleId]);
    $block=$db->prepare('INSERT INTO article_blocks(article_id,sort_order,kind,heading,content,collapsed) VALUES (?,?,?,?,?,?)');
    foreach($blocks as $i=>$v) $block->execute([$articleId,$i,$v['kind'],$v['heading'],$v['content'],$v['collapsed']]);
}

function article_extra_categories(PDO $db,int $articleId): array {
    $s=$db->prepare('SELECT category_id FROM article_categories WHERE article_id=?');$s->execute([$articleId]);return array_map('intval',$s->fetchAll(PDO::FETCH_COLUMN));
}

function article_blocks(PDO $db,int $articleId): array {
    try{$s=$db->prepare('SELECT kind,heading,content,collapsed FROM article_blocks WHERE article_id=? ORDER BY sort_order,id');$s->execute([$articleId]);return $s->fetchAll();}catch(PDOException){return [];}
}

