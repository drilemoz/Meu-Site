<?php
        if($action==='news_gemini_suggest'){
            require_ability('write',$me);
            $articleId=(int)($_POST['id']??0);
            if($articleId){
                $stmt=$db->prepare('SELECT id,author_id,status FROM articles WHERE id=? LIMIT 1');
                $stmt->execute([$articleId]);$current=$stmt->fetch();
                if(!$current||!can_edit_article($me,$current))throw new RuntimeException('Sem permissão para revisar esta notícia.');
            }
            $last=(int)($_SESSION['igua_gemini_suggest_time']??0);
            if(time()-$last<20)throw new RuntimeException('Aguarde 20 segundos para uma nova sugestão do Gemini.');
            $cfg=igua_gemini_read();
            if($cfg===null || !$cfg['enabled'])throw new RuntimeException('Gemini indisponível. Ative e teste a API em Integrações.');
            foreach(['task','title','summary','body'] as $field)
                if(!is_string($_POST[$field]??null))throw new InvalidArgumentException('Texto de entrada inválido.');
            $job=igua_gemini_prompt($_POST['task'],$_POST['title'],$_POST['summary'],$_POST['body']);
            $_SESSION['igua_gemini_suggest_time']=time(); // registra antes de consumir cota
            $answer=igua_gemini_send($cfg,$job['prompt'],($_POST['task']==='body'?2200:350));
            $answer=trim($answer);
            if(strlen($answer)>$job['limit']*5 || ulen($answer)>$job['limit'])
                throw new RuntimeException('A sugestão excedeu o tamanho do campo. Edite sua matéria antes de repetir.');
            header('Content-Type: application/json; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');
            echo json_encode(['ok'=>true,'field'=>$job['field'],'suggestion'=>$answer],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);
            exit;
        }
