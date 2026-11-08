<?php
        if($action==='news_create_category'||$action==='news_create_source'){
            require_ability('manage_taxonomy',$me);
            $name=trim((string)($_POST['name']??''));
            $isCategory=$action==='news_create_category';
            if(ulen($name)<2||ulen($name)>($isCategory?75:160))throw new RuntimeException('Nome inválido.');
            if($isCategory){
                $base=slug($name);$candidate=$base;$suffix=2;
                while(true){$lookup=$db->prepare('SELECT id FROM categories WHERE slug=?');$lookup->execute([$candidate]);if(!$lookup->fetchColumn())break;$candidate=$base.'-'.$suffix++;}
                $db->prepare('INSERT INTO categories(name,slug,sort_order) VALUES(?,?,100)')->execute([$name,$candidate]);
            }else{
                $url=trim((string)($_POST['url']??''));
                if(ulen($url)>600||($url!==''&&(!filter_var($url,FILTER_VALIDATE_URL)||!str_starts_with($url,'https://'))))throw new RuntimeException('Endereço HTTPS inválido.');
                $db->prepare('INSERT INTO editorial_sources(name,url) VALUES(?,?)')->execute([$name,$url]);
            }
            header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
            echo json_encode(['saved'=>true,'id'=>(int)$db->lastInsertId(),'name'=>$name],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);exit;
        }
