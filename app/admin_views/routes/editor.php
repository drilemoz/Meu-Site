<?php 
    $row=['id'=>0,'title'=>'','summary'=>'','body'=>'','category_id'=>'','status'=>'draft','featured'=>0,'image'=>null,'image_caption'=>'','image_credit'=>'','source_name'=>'','source_url'=>'','author_profile_id'=>null,'source_id'=>null,'seo_title'=>'','seo_description'=>'','slug'=>''];
    if ($action==='edit') { $s=$db->prepare('SELECT * FROM articles WHERE id=?');$s->execute([(int)($_GET['id']??0)]);$row=$s->fetch()?:$row;if($row['id'] && !can_edit_article($me,$row)){http_response_code(403);exit('Você não pode editar esta notícia.');} }
    $profiles=$db->query('SELECT id,name FROM author_profiles ORDER BY name')->fetchAll();
    $sources=$db->query('SELECT id,name FROM editorial_sources ORDER BY name')->fetchAll();
    require_once __DIR__.'/../../portal_options.php';
    $allCats=$db->query('SELECT id,name FROM categories ORDER BY sort_order,name')->fetchAll();
    $cats=igua_portal_active_categories($allCats,setting('portal_active_categories',''));
    $preserve=$row['id']?[(int)$row['category_id'],...array_map('intval',article_extra_categories($db,(int)$row['id']))]:[];
    foreach($allCats as $c)if(in_array((int)$c['id'],$preserve,true)&&!in_array((int)$c['id'],array_map('intval',array_column($cats,'id')),true))$cats[]=$c; ?>
    <?php news_admin_editor($db,$row,$me,$profiles,$sources,$cats); ?>
