<?php
declare(strict_types=1);
/** Testes reais em MariaDB isolado: somente CI, nunca produção. */
$name=(string)(getenv('IGUA_TEST_DB_NAME')?:'');
if(PHP_SAPI!=='cli' || getenv('IGUA_TEST_ALLOW_WRITE')!=='YES_ISOLATED_DB' ||
    !preg_match('/^igua_ci_[a-z0-9_]{3,40}$/D',$name) ||
    !in_array((string)getenv('IGUA_TEST_DB_HOST'),['127.0.0.1','localhost','::1'],true) ||
    !extension_loaded('pdo_mysql')){
    fwrite(STDERR,"LEGAL196 bloqueado: banco isolado igua_ci_* e PDO MySQL exigidos.\n");exit(2);
}
require_once __DIR__.'/../../app/bootstrap.php';
require_once __DIR__.'/../../app/schema.php';
require_once __DIR__.'/../../app/schema_migrations.php';
require_once __DIR__.'/../../app/apps_legal.php';
require_once __DIR__.'/../../app/backup.php';
$db=connection(['host'=>getenv('IGUA_TEST_DB_HOST'),'port'=>(int)(getenv('IGUA_TEST_DB_PORT')?:3306),
    'database'=>$name,'username'=>getenv('IGUA_TEST_DB_USER'),'password'=>getenv('IGUA_TEST_DB_PASSWORD')]);
function legal196mysql(bool $ok,string $what):void{if(!$ok)throw new RuntimeException('LEGAL 196 MariaDB: '.$what);echo 'OK: '.$what."\n";}
legal196mysql(igua_schema_revision($db)===37,'migração para schema 37');
legal196mysql((int)$db->query("SELECT COUNT(*) FROM legal_apps WHERE slug='uaicortes' AND status='draft'")->fetchColumn()===1,'Uai Cortes criado como rascunho inicial');
$base='legal-ci-'.bin2hex(random_bytes(4));$ids=[];$backupFile=null;
try{
    foreach([$base.'-a',$base.'-b'] as $slug){
        igua_app_assert_slug($db,$slug);
        $db->prepare('INSERT INTO legal_apps (name,slug,summary,description,developer_name,support_email) VALUES (?,?,?,?,?,?)')
            ->execute(['Aplicativo de integração '.$slug,$slug,'Resumo real de teste','Descrição do teste','Integração CI','ci@example.invalid']);
        $appId=(int)$db->lastInsertId();$ids[]=$appId;
        igua_app_seed_default_pages($db,$appId,'App de integração');
    }
    legal196mysql(count($ids)===2 && $ids[0]!==$ids[1],'dois aplicativos cadastrados com slugs diferentes');
    $collision=false;try{igua_app_assert_slug($db,$base.'-a');}catch(InvalidArgumentException){$collision=true;}
    legal196mysql($collision,'colisão de slug bloqueada');
    $firstSlug=$base.'-a';$otherSlug=$base.'-b';
    legal196mysql(igua_app_public($db,$firstSlug,'inicio')===null,'rascunho nunca servido ao público');
    $q=$db->prepare('SELECT id,slug FROM legal_app_pages WHERE app_id=? ORDER BY id');$q->execute([$ids[0]]);
    $pages=$q->fetchAll(PDO::FETCH_ASSOC);
    legal196mysql(count($pages)===3,'páginas iniciais de apresentação, privacidade e termos');
    $homeId=0;foreach($pages as $row)if($row['slug']==='inicio')$homeId=(int)$row['id'];
    legal196mysql($homeId>0,'página inicial encontrada');
    $body1='Texto publicado v1 para teste de integração CI. Sem informações jurídicas ou pessoais reais.';
    $body2='Texto publicado v2 para teste de integração CI. Alteração visível, histórico anterior preservado.';
    $db->prepare('UPDATE legal_app_pages SET draft_title=?, draft_body=?,draft_seo_description=? WHERE id=?')
        ->execute(['Apresentação CI v1',$body1,'SEO do teste de integração',$homeId]);
    $db->prepare('INSERT INTO legal_app_revisions (page_id,revision_number,title,body,seo_description) VALUES (?,?,?,?,?)')
        ->execute([$homeId,1,'Apresentação CI v1',$body1,'SEO do teste de integração']);
    $rev1=(int)$db->lastInsertId();
    $db->prepare("UPDATE legal_app_pages SET published_revision_id=?, status='published' WHERE id=?")->execute([$rev1,$homeId]);
    legal196mysql(igua_app_public($db,$firstSlug,'inicio')===null,'página publicada em aplicativo não publicado permanece privada');
    $db->prepare("UPDATE legal_apps SET status='published' WHERE id=?")->execute([$ids[0]]);
    $public=igua_app_public($db,$firstSlug,'inicio');
    legal196mysql($public!==null && $public['title']==='Apresentação CI v1','página pública renderiza revisão publicada');
    $db->prepare('UPDATE legal_app_pages SET draft_title=?,draft_body=? WHERE id=?')->execute(['Rascunho novo',$body2,$homeId]);
    $public=igua_app_public($db,$firstSlug,'inicio');
    legal196mysql($public!==null && $public['title']==='Apresentação CI v1' && $public['body']===$body1,'editar rascunho não altera texto público');
    $db->prepare('INSERT INTO legal_app_revisions (page_id,revision_number,title,body) VALUES (?,?,?,?)')
        ->execute([$homeId,2,'Apresentação CI v2',$body2]);
    $rev2=(int)$db->lastInsertId();
    $db->prepare('UPDATE legal_app_pages SET published_revision_id=? WHERE id=?')->execute([$rev2,$homeId]);
    $public=igua_app_public($db,$firstSlug,'inicio');
    legal196mysql($public!==null && $public['body']===$body2,'republicação substitui o conteúdo público sem alterar URL');
    $db->prepare('UPDATE legal_app_pages SET draft_title=?,draft_body=? WHERE id=?')->execute(['Apresentação CI v1',$body1,$homeId]);
    legal196mysql(igua_app_public($db,$firstSlug,'inicio')['body']===$body2,'restaurar revisão para rascunho não retrocede publicação');
    legal196mysql(igua_app_public($db,$otherSlug,'inicio')===null,'publicação independente entre aplicativos');
    $db->prepare('INSERT INTO legal_app_slug_redirects (old_slug,app_id) VALUES (?,?)')->execute([$base.'-antigo',$ids[0]]);
    legal196mysql(igua_app_slug_alias($db,$base.'-antigo','inicio')===$firstSlug,'alias de slug antigo localiza URL nova');
    $backupFile=make_backup($db,'manual');
    $path=backup_path($backupFile);
    $data=parse_backup((string)file_get_contents($path),$backupFile);
    legal196mysql(count($data['tables']['legal_apps'])>=3 && count($data['tables']['legal_app_revisions'])>=2,'backup v12 inclui vários apps e revisões');
    $db->prepare('UPDATE legal_app_pages SET published_revision_id=? WHERE id=?')->execute([$rev1,$homeId]);
    restore_backup($db,$data);
    legal196mysql(igua_app_public($db,$firstSlug,'inicio')['body']===$body2,'restauração preserva revisão pública publicada');
    legal196mysql(igua_app_slug_alias($db,$base.'-antigo','inicio')===$firstSlug,'backup/restauração preserva redirecionamento');
    $db->prepare("UPDATE legal_apps SET status='draft' WHERE id=?")->execute([$ids[0]]);
    legal196mysql(igua_app_public($db,$firstSlug,'inicio')===null,'despublicação retorna 404 sem apagar revisões');
}finally{
    foreach($ids as $id)$db->prepare('DELETE FROM legal_apps WHERE id=?')->execute([$id]);
    if($backupFile!==null){$path=backup_path($backupFile);if(is_string($path) && is_file($path))@unlink($path);}
}
echo "Módulo Aplicativos e Páginas Legais: teste MariaDB concluído.\n";
