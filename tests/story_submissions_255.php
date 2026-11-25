<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/story_submissions.php';
require_once __DIR__.'/../app/backup.php';
require_once __DIR__.'/../app/editorial.php';
require_once __DIR__.'/../app/admin_controllers/post_routes.php';
function s255(bool $ok,string $name):void{if(!$ok)throw new RuntimeException($name);echo "OK: $name\n";}
function reject255(callable $f):bool{try{$f();return false;}catch(DomainException){return true;}}
$input=['name'=>'Maria Teste','contact'=>'maria@example.invalid','title'=>'Uma notícia local','message'=>'Relato de um acontecimento local para verificação.','consent'=>'1','website'=>''];
s255(igua_story_validate($input)['title']===$input['title'],'campos válidos');
s255(igua_story_validate(array_replace($input,['contact'=>'(37) 99999-9999']))['contact']==='(37) 99999-9999','telefone válido');
foreach(['contact'=>'invalido','title'=>'','message'=>'curto','consent'=>'0','website'=>'spam','name'=>['x']] as $k=>$v)s255(reject255(fn()=>igua_story_validate(array_replace($input,[$k=>$v]))),'rejeita '.$k);
$png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WlmuSAAAAAASUVORK5CYII=',true);
s255(igua_story_photo($png)['data']===base64_encode($png),'foto real preservada');
s255(reject255(fn()=>igua_story_photo('<svg xmlns="http://www.w3.org/2000/svg"/>')),'SVG recusado');
s255(reject255(fn()=>igua_story_photo(str_repeat('x',IGUA_STORY_PHOTO_MAX+1))),'foto grande recusada');
s255(igua_story_uploads([])===[],'fotos opcionais');
s255(!role_allowed('author','edit_all')&&role_allowed('editor','edit_all'),'controle de papéis');
s255(igua_admin_post_module('story_submissions')==='story_submissions','rota POST explícita');
foreach([12=>BACKUP_TABLES_V12,13=>BACKUP_TABLES] as $v=>$names){$tables=array_fill_keys($names,[]);$tables['users']=[['id'=>1,'role'=>'admin','active'=>1]];if($v===13)$tables['story_submissions']=[['id'=>1,'photos'=>json_encode([igua_story_photo($png)])]];$parsed=parse_backup(json_encode(['format'=>'iguanews-backup','version'=>$v,'tables'=>$tables]),'story.json');s255(array_keys($parsed['tables'])===BACKUP_TABLES && count($parsed['tables']['story_submissions'])===($v===13?1:0),'backup v'.$v);}
$public=file_get_contents(__DIR__.'/../public/index.php');$admin=file_get_contents(__DIR__.'/../public/admin.php');
s255(str_contains($public,'igua_story_receive($db,$_POST')&&str_contains($public,'check_csrf();'),'recebimento POST e CSRF');
s255(str_contains($admin,"'story_photo'=>'edit_all'")&&str_contains($admin,"'story_submissions'=>'edit_all'"),'rotas administrativas protegidas');
