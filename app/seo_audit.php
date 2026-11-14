<?php
declare(strict_types=1);
/** Diagnóstico editorial local. Não representa nota do Google ou previsão de ranking. */
function igua_seo_audit(array $article): array {
    $title=trim((string)(($article['seo_title']??'') ?: ($article['title']??'')));
    $headline=trim((string)($article['title']??''));
    $summary=trim((string)($article['summary']??''));
    $desc=trim((string)(($article['seo_description']??'') ?: ($summary!==''?$summary:news_seo_description($headline,'',(string)($article['body']??'')))));
    $body=trim(strip_tags((string)($article['body']??'')));
    $slug=(string)($article['slug']??'');
    $image=trim((string)($article['image']??''));
    $alt=trim((string)(($article['cover_alt']??'') ?: ($article['image_caption']??'')));
    $checks=[
      ['id'=>'title','label'=>'Título claro e informativo (20 a 90 caracteres)','ok'=>ulen($title)>=20&&ulen($title)<=90,'weight'=>20],
      ['id'=>'description','label'=>'Descrição de busca objetiva (50 a 160 caracteres)','ok'=>ulen($desc)>=50&&ulen($desc)<=160,'weight'=>15],
      ['id'=>'slug','label'=>'Endereço legível e válido (3 a 120 caracteres)','ok'=>strlen($slug)>=3&&strlen($slug)<=120&&preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)===1,'weight'=>15],
      ['id'=>'summary','label'=>'Resumo para o leitor (40 a 240 caracteres)','ok'=>ulen($summary)>=40&&ulen($summary)<=240,'weight'=>15],
      ['id'=>'body','label'=>'Texto informativo ou blocos editoriais','ok'=>ulen($body)>=100 || !empty($article['has_blocks']),'weight'=>15],
      ['id'=>'image','label'=>'Foto de capa selecionada','ok'=>$image!=='','weight'=>10],
      ['id'=>'alt','label'=>'Legenda ou texto alternativo descritivo da capa','ok'=>$image!==''&&ulen($alt)>=10,'weight'=>10],
    ];
    $score=0;foreach($checks as $check)if($check['ok'])$score+=$check['weight'];
    return ['score'=>$score,'checks'=>$checks,'issues'=>array_values(array_map(static fn($c)=>$c['label'],array_filter($checks,static fn($c)=>!$c['ok'])))];
}
