<?php
declare(strict_types=1);
/** Valida todos os campos antes de iniciar qualquer gravação no banco. */
function igua_settings_validate(PDO $db,array $post): array {
    $limits=['site_name'=>80,'site_tagline'=>200,'city'=>100,'contact_email'=>190,'seo_title_template'=>220,'seo_default_description'=>320,'seo_social_image'=>600,'seo_site_url'=>240,'seo_index'=>1,'seo_pretty_urls'=>1,'seo_home_title'=>220,'seo_favicon_image'=>600,'seo_page_title_onibus'=>220,'seo_page_title_sobre'=>220,'seo_page_title_contato'=>220,'seo_page_title_anuncie'=>220,'seo_page_title_privacidade'=>220,'seo_page_title_termos'=>220];
    $result=[];
    foreach($limits as $key=>$max){
        if(in_array($key,['seo_index','seo_pretty_urls'],true)){
            $result[$key]=isset($post[$key])?'1':'0';
            continue;
        }
        $raw=$post[$key]??'';
        if(!is_string($raw))throw new InvalidArgumentException('Campo inválido: '.$key);
        $v=trim($raw);
        if(ulen($v)>$max)throw new InvalidArgumentException('Valor muito longo: '.$key);
        if($key==='site_name')$v=igua_validate_brand_name($v);
        if($key==='seo_site_url')$v=safe_seo_url($v);
        if(in_array($key,['seo_social_image','seo_favicon_image'],true) && $v!==''){
            if(str_starts_with($v,'/uploads/')){
                $rel=rawurldecode(substr($v,strlen('/uploads/')));
                if(!media_valid_name($rel))throw new InvalidArgumentException('Imagem ausente da biblioteca.');
                $check=$db->prepare('SELECT id FROM media_library WHERE filename=? AND deleted_at IS NULL');
                $check->execute([$rel]);
                if(!$check->fetchColumn())throw new InvalidArgumentException('Imagem ausente da biblioteca.');
            } elseif (!filter_var($v,FILTER_VALIDATE_URL)||!str_starts_with($v,'https://')) {
                throw new InvalidArgumentException('Imagem deve ser URL HTTPS completa ou foto da biblioteca.');
            }
        }
        if($key==='seo_favicon_image' && $v!=='' && !preg_match('/\.(png|jpe?g|webp|ico)(?:\?.*)?$/i',parse_url($v,PHP_URL_PATH)?:''))throw new InvalidArgumentException('Favicon deve ser PNG, JPG, WebP ou ICO.');
        if($key==='seo_title_template' && !str_contains($v,'%title%'))throw new InvalidArgumentException('Modelo de título precisa incluir %title%.');
        if(str_starts_with($key,'seo_page_title_') || $key==='seo_home_title')$v=clean_seo_text($v,220);
        if($key==='contact_email' && !filter_var($v,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('E-mail de contato inválido.');
        $result[$key]=$v;
    }
    return $result;
}
/** InnoDB: erro em qualquer campo implica rollback de todos os campos. */
function igua_settings_save(PDO $db,array $post): void {
    $validated=igua_settings_validate($db,$post);
    $db->beginTransaction();
    try {
        $stmt=$db->prepare('INSERT INTO settings (name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
        foreach($validated as $key=>$value)$stmt->execute([$key,$value]);
        $db->commit();
    } catch(Throwable $e){
        if($db->inTransaction())$db->rollBack();
        throw $e;
    }
}
