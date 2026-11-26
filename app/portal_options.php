<?php
declare(strict_types=1);
/** Configurações editoriais sem alterar schema ou apagar categorias históricas. */
function igua_portal_active_categories(array $cats,string $raw): array {
    $ids=json_decode($raw,true);
    if(!is_array($ids)||!array_is_list($ids))return $cats;
    return array_values(array_filter($cats,static fn($c)=>in_array((int)$c['id'],$ids,true)));
}
function igua_portal_options(string $raw=''): array {
    $default=['featured'=>true,'bus'=>true,'recent'=>true,'sections'=>true,'popular'=>false,'recent_count'=>3,'section_count'=>1,'max_age'=>14,'labels'=>false,'order'=>['featured','bus','recent','sections','popular'],'logo'=>'','heading'=>'PORTAL DE NOTÍCIAS','accent'=>'#095ef0','nav_local'=>'auto','nav_events'=>'auto'];
    $v=json_decode($raw,true);if(!is_array($v))return $default;
    foreach(['featured','bus','recent','sections','popular','labels'] as $k)if(isset($v[$k])&&is_bool($v[$k]))$default[$k]=$v[$k];
    foreach(['recent_count'=>[1,8],'section_count'=>[1,3],'max_age'=>[1,90]] as $k=>[$min,$max])if(isset($v[$k])&&is_int($v[$k])&&$v[$k]>=$min&&$v[$k]<=$max)$default[$k]=$v[$k];
    if(isset($v['order'])&&is_array($v['order'])&&count($v['order'])===5&&!array_diff($default['order'],$v['order']))$default['order']=array_values($v['order']);
    foreach(['nav_local','nav_events'] as $k)if(isset($v[$k])&&is_string($v[$k])&&preg_match('/^(auto|none|[1-9][0-9]*)$/D',$v[$k]))$default[$k]=$v[$k];
    if(isset($v['logo'])&&is_string($v['logo'])&&preg_match('~^/uploads/[a-zA-Z0-9_./%\-]+$~D',$v['logo'])&&!str_contains(rawurldecode($v['logo']),'..'))$default['logo']=$v['logo'];
    if(isset($v['heading'])&&is_string($v['heading'])&&strlen($v['heading'])<=160)$default['heading']=$v['heading'];
    if(isset($v['accent'])&&is_string($v['accent'])&&preg_match('/^#[0-9a-fA-F]{6}$/D',$v['accent']))$default['accent']=$v['accent'];
    return $default;
}
function igua_portal_validate_options(PDO $db,array $post,array $cats): array {
    $v=igua_portal_options();
    foreach(['featured','bus','recent','sections','popular','labels'] as $k)$v[$k]=isset($post['portal_'.$k]);
    foreach(['recent_count'=>[1,8],'section_count'=>[1,3],'max_age'=>[1,90]] as $k=>[$min,$max]){
        $n=filter_var($post['portal_'.$k]??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>$min,'max_range'=>$max]]);if($n===false)throw new InvalidArgumentException('Quantidade ou prazo inválido.');$v[$k]=$n;
    }
    $ranks=[];foreach($v['order'] as $key){$n=filter_var($post['portal_order_'.$key]??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>5]]);if($n===false)throw new InvalidArgumentException('Ordem inválida.');$ranks[$key]=$n;}
    asort($ranks,SORT_NUMERIC);$v['order']=array_keys($ranks);
    $known=array_map(static fn($c)=>(int)$c['id'],$cats);
    foreach(['nav_local','nav_events'] as $k){$s=$post['portal_'.$k]??'auto';if(!is_string($s)||(!in_array($s,['auto','none'],true)&&(!ctype_digit($s)||!in_array((int)$s,$known,true))))throw new InvalidArgumentException('Atalho inválido.');$v[$k]=$s;}
    $heading=$post['portal_heading']??'';$accent=$post['portal_accent']??'';$logo=$post['portal_logo']??'';
    if(!is_string($heading)||strlen($heading)>160||!is_string($accent)||!preg_match('/^#[0-9a-fA-F]{6}$/D',$accent)||!is_string($logo)||strlen($logo)>600)throw new InvalidArgumentException('Identidade visual inválida.');
    if($logo!==''){
        if(!str_starts_with($logo,'/uploads/'))throw new InvalidArgumentException('Use o caminho de uma imagem da biblioteca (/uploads/...).');
        $rel=rawurldecode(substr($logo,9));if(!media_valid_name($rel)||!preg_match('/\.(png|jpe?g|webp|gif)$/iD',$rel))throw new InvalidArgumentException('Logotipo inválido.');
        $s=$db->prepare('SELECT id FROM media_library WHERE filename=? AND deleted_at IS NULL');$s->execute([$rel]);if(!$s->fetchColumn())throw new InvalidArgumentException('Logotipo não encontrado na biblioteca.');
    }
    $v['heading']=trim($heading);$v['accent']=$accent;$v['logo']=$logo===''?'':media_url($rel);
    return ['portal_options'=>json_encode($v,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)];
}
function igua_portal_setting_write(PDO $db,string $key,string $value):void {
    $db->prepare('INSERT INTO settings (name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)')->execute([$key,$value]);
}
function igua_portal_add_active(PDO $db,int $id):void {
    $s=$db->prepare('SELECT value FROM settings WHERE name=?');$s->execute(['portal_active_categories']);$ids=json_decode((string)$s->fetchColumn(),true);
    if(is_array($ids)&&array_is_list($ids)){ $ids[]=$id;igua_portal_setting_write($db,'portal_active_categories',json_encode(array_values(array_unique($ids)),JSON_THROW_ON_ERROR)); }
}
function igua_portal_nav_category(array $cats,string $value,array $slugs):?array {
    foreach($cats as $c)if($value==='auto'?in_array($c['slug'],$slugs,true):(string)$c['id']===$value)return $c;
    return null;
}
/** Preset explícito; não presume a abrangência de matérias temáticas anteriores. */
function igua_portal_local_preset(PDO $db):void {
    $db->beginTransaction();try {
        $cats=$db->query('SELECT id,slug FROM categories ORDER BY sort_order,id')->fetchAll(PDO::FETCH_ASSOC);
        $ids=[];$defs=[['Iguatama',['iguatama','cidade'],'iguatama'],['Região',['regiao','regional','noticias-da-regiao'],'regiao'],['Minas Gerais',['minas-gerais','mg'],'minas-gerais'],['Brasil',['brasil'],'brasil'],['Mundo',['mundo','internacional'],'mundo']];
        foreach($defs as $i=>[$name,$aliases,$slug]){
            $id=0;foreach($aliases as $alias){foreach($cats as $c)if($c['slug']===$alias){$id=(int)$c['id'];break 2;}}
            if($id){$db->prepare('UPDATE categories SET name=?,sort_order=? WHERE id=?')->execute([$name,$i,$id]);}
            else{$db->prepare('INSERT INTO categories(name,slug,sort_order) VALUES(?,?,?)')->execute([$name,$slug,$i]);$id=(int)$db->lastInsertId();}
            $ids[]=$id;
        }
        igua_portal_setting_write($db,'portal_active_categories',json_encode($ids,JSON_THROW_ON_ERROR));
        igua_portal_setting_write($db,'home_category_ids',json_encode([$ids[0]],JSON_THROW_ON_ERROR));
        igua_portal_setting_write($db,'home_region_category',(string)$ids[1]);
        
        $s=$db->prepare('SELECT value FROM settings WHERE name=?');$s->execute(['portal_options']);$existing=igua_portal_options((string)$s->fetchColumn());$opts=igua_portal_options();
        foreach(['logo','heading','accent'] as $key)$opts[$key]=$existing[$key];
        igua_portal_setting_write($db,'portal_options',json_encode($opts,JSON_THROW_ON_ERROR));
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
