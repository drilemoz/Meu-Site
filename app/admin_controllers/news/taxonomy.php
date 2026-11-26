<?php
        if(in_array($action,['news_create_category','news_create_source','news_create_author'],true)){
            require_ability('manage_taxonomy',$me);
            $name=trim((string)($_POST['name']??''));
            $maxLength=match($action){'news_create_category'=>75,'news_create_author'=>120,default=>160};
            if(ulen($name)<2||ulen($name)>$maxLength)throw new RuntimeException('Nome inválido.');
            if($action==='news_create_category'){
                $base=slug($name);$candidate=$base;$suffix=2;
                while(true){$lookup=$db->prepare('SELECT id FROM categories WHERE slug=?');$lookup->execute([$candidate]);if(!$lookup->fetchColumn())break;$candidate=$base.'-'.$suffix++;}
                $db->prepare('INSERT INTO categories(name,slug,sort_order) VALUES(?,?,100)')->execute([$name,$candidate]);
                $createdCategoryId=(int)$db->lastInsertId();require_once __DIR__.'/../../portal_options.php';igua_portal_add_active($db,$createdCategoryId);
            }elseif($action==='news_create_author'){
                $db->prepare('INSERT INTO author_profiles(name) VALUES(?)')->execute([$name]);
            }else{
                $url=trim((string)($_POST['url']??''));
                if(ulen($url)>600||($url!==''&&(!filter_var($url,FILTER_VALIDATE_URL)||!str_starts_with($url,'https://'))))throw new RuntimeException('Endereço HTTPS inválido.');
                $db->prepare('INSERT INTO editorial_sources(name,url) VALUES(?,?)')->execute([$name,$url]);
            }
            header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
            echo json_encode(['saved'=>true,'id'=>$createdCategoryId??(int)$db->lastInsertId(),'name'=>$name],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);exit;
        }
