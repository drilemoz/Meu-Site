<?php
declare(strict_types=1);
require_once __DIR__.'/ads_examples.php';
require_once __DIR__.'/ads.php';
require_once __DIR__.'/adsense_review.php';
require_once __DIR__.'/ads_management.php';
function ad_admin_post(PDO $db,string $action): never {
    if($action==='ads_delete_demo') {
        if(!hash_equals('REMOVER ANUNCIOS',trim((string)($_POST['confirmation']??''))))
            throw new DomainException('Digite REMOVER ANUNCIOS para excluir apenas campanhas demonstrativas.');
        $count=igua_ad_demo_count($db);
        if($count<1){flash('Nenhuma campanha de demonstração encontrada.');redirect('/admin.php?action=ads');}
        $backup=make_backup($db,'manual');
        $removed=igua_ad_remove_demo_campaigns($db);
        flash($removed.' anúncio(s) de demonstração removido(s). Backup criado: '.$backup.'. Anúncios reais preservados.');
        $return=(string)($_POST['return_to']??'');
        redirect($return==='maintenance'?'/admin.php?action=maintenance':'/admin.php?action=ads');
    }
    if($action==='ads_save_settings') {
        $hasPublisher=array_key_exists('publisher',$_POST);
        $publisher=$hasPublisher?ad_validate_publisher((string)$_POST['publisher']):'';
        $enabled=isset($_POST['ads_enabled'])?'1':'0';
        $set=$db->prepare('INSERT INTO settings (name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
        if($hasPublisher)$set->execute(['adsense_publisher',$publisher]);
        $set->execute(['ads_enabled',$enabled]);
        flash('Publicidade: configurações atualizadas.');redirect('/admin.php?action=ads');
    }
    if($action==='ads_prepare_ads_txt') {
        igua_adsense_ads_txt_write(setting('adsense_publisher',''), IGUA_PUBLIC);
        flash('ads.txt publicado. Confira o arquivo no domínio e valide-o no Google AdSense.');
        redirect('/admin.php?action=ads');
    }
    if($action==='ads_save_slot') {
        $slug=(string)($_POST['slot']??'');$provider=(string)($_POST['provider']??'off');
        if(!ad_valid_slot($slug)||!in_array($provider,AD_PROVIDERS,true))throw new RuntimeException('Posição ou provedor inválido.');
        $unit=trim((string)($_POST['adsense_slot']??''));
        if($unit!==''&&!preg_match('/^\d{6,32}$/D',$unit))throw new RuntimeException('Informe um ID numérico de bloco AdSense válido.');
        $enabled=$provider==='off'?0:(isset($_POST['enabled'])?1:0);
        if($enabled&&$provider==='external')throw new RuntimeException('Adaptador externo indisponível para ativação.');
        if($enabled&&$provider==='adsense'&&($unit===''||setting('adsense_publisher','')===''))
            throw new RuntimeException('Configure o editor ca-pub e o ID numérico do bloco AdSense.');
        $db->beginTransaction();
        try {
            $guard=$db->prepare('SELECT slug FROM ad_placements WHERE slug=? FOR UPDATE');$guard->execute([$slug]);
            if(!$guard->fetch())throw new RuntimeException('Posição não encontrada.');
            if($provider==='adsense'&&$enabled) {
                $occupied=$db->prepare('SELECT id FROM ad_campaigns WHERE placement_slug=? AND active=1 AND (end_date IS NULL OR end_date>=CURDATE()) LIMIT 1 FOR UPDATE');
                $occupied->execute([$slug]);
                if($occupied->fetch())throw new DomainException('Esta posição tem campanhas locais ativas. Pause-as antes de ativar o AdSense.');
            }
            $stmt=$db->prepare('UPDATE ad_placements SET provider=?,enabled=?,adsense_slot=? WHERE slug=?');
            $stmt->execute([$provider,$enabled,$unit,$slug]);
            $db->commit();
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
        flash('Posição '.strtoupper($slug).' atualizada.');redirect('/admin.php?action=ads');
    }
    if($action==='ads_save_campaign') {
        $id=max(0,(int)($_POST['id']??0));$slug=(string)($_POST['placement_slug']??'');
        if(!ad_valid_slot($slug))throw new RuntimeException('Posição de publicidade inválida.');
        $title=trim((string)($_POST['title']??''));$advertiser=trim((string)($_POST['advertiser']??''));$description=trim((string)($_POST['description']??''));
        if(ulen($title)<3||ulen($title)>130||ulen($advertiser)<2||ulen($advertiser)>130||ulen($description)>220)
            throw new RuntimeException('Confira título (3–130), anunciante (2–130) e descrição (até 220 caracteres).');
        $target=ad_validate_url((string)($_POST['target_url']??''));
        $start=ad_validate_day((string)($_POST['start_date']??''),true);
        $end=ad_validate_day((string)($_POST['end_date']??''),false);
        if($end!==null&&$end<$start)throw new RuntimeException('Período final anterior ao início.');
        $priority=(int)($_POST['priority']??0);if($priority<0||$priority>100)throw new RuntimeException('Prioridade deve estar entre 0 e 100.');
        $active=isset($_POST['active'])?1:0;
        $allowOverlap=isset($_POST['allow_overlap']);
        $prepareLocal=isset($_POST['prepare_local_slot']);
        $db->beginTransaction();
        try {
            $locked=$db->prepare('SELECT * FROM ad_placements WHERE slug=? FOR UPDATE');$locked->execute([$slug]);
            $slot=$locked->fetch();if(!$slot)throw new DomainException('Posição inexistente.');
            $old=null;
            if($id){$q=$db->prepare('SELECT * FROM ad_campaigns WHERE id=? FOR UPDATE');$q->execute([$id]);$old=$q->fetch();if(!$old)throw new DomainException('Campanha não encontrada.');}
            if($active){
                if($slot['provider']==='adsense'&&(int)$slot['enabled']===1)
                    throw new DomainException('Esta posição está dedicada ao AdSense. Altere a posição primeiro.');
                if(($slot['provider']!=='local'||!(int)$slot['enabled'])&&!$prepareLocal)
                    throw new DomainException('Posição desativada ou não local. Marque "Preparar posição para anúncios locais" ou configure a posição.');
                if($slot['provider']==='external'||($slot['provider']==='adsense'&&(int)$slot['enabled']===1))
                    throw new DomainException('Provedor incompatível. Altere a posição primeiro.');
                $q=$db->prepare('SELECT id,placement_slug,title,start_date,end_date,active FROM ad_campaigns WHERE placement_slug=? AND active=1 FOR UPDATE');
                $q->execute([$slug]);
                $conflicts=igua_ad_overlapping_campaigns($q->fetchAll(),$slug,$start,$end,$id);
                if($conflicts&&!$allowOverlap)
                    throw new DomainException('Já existe campanha ativa com datas coincidentes em '.strtoupper($slug).'. Escolha outro período/posição ou confirme o revezamento.');
            }
            // A validação foi concluída; preserva mídia antiga quando não há upload novo.
            $chosen=trim((string)($_POST['library_campaign']??''));
            if($chosen!==''){
                if(!news_library_resolve($chosen))throw new RuntimeException('Banner indisponível.');
                $probe=$db->prepare('SELECT id FROM media_library WHERE filename=? AND deleted_at IS NULL');$probe->execute([$chosen]);
                if(!$probe->fetchColumn())throw new RuntimeException('Banner removido da biblioteca.');
                $image=$chosen;
            }else $image=image_upload('campaign_image',$old['image']??null);
            if($active&&$prepareLocal&&($slot['provider']!=='local'||!(int)$slot['enabled'])){
                $s=$db->prepare("UPDATE ad_placements SET provider='local',enabled=1 WHERE slug=?");$s->execute([$slug]);
            }
            if($id){
                $s=$db->prepare('UPDATE ad_campaigns SET placement_slug=?,title=?,advertiser=?,description=?,target_url=?,image=?,start_date=?,end_date=?,priority=?,active=? WHERE id=?');
                $s->execute([$slug,$title,$advertiser,$description,$target,$image,$start,$end,$priority,$active,$id]);
            }else{
                $s=$db->prepare('INSERT INTO ad_campaigns (placement_slug,title,advertiser,description,target_url,image,start_date,end_date,priority,active) VALUES (?,?,?,?,?,?,?,?,?,?)');
                $s->execute([$slug,$title,$advertiser,$description,$target,$image,$start,$end,$priority,$active]);
            }
            $db->commit();
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
        flash('Campanha salva.');redirect('/admin.php?action=ads');
    }
    if($action==='ads_toggle_campaign'){
        $id=max(0,(int)($_POST['id']??0));
        $db->beginTransaction();
        try {
            $s=$db->prepare('SELECT * FROM ad_campaigns WHERE id=? FOR UPDATE');$s->execute([$id]);$row=$s->fetch();
            if(!$row)throw new DomainException('Campanha não encontrada.');
            if(!(int)$row['active']) {
                $slot=$db->prepare('SELECT * FROM ad_placements WHERE slug=? FOR UPDATE');$slot->execute([$row['placement_slug']]);$p=$slot->fetch();
                if(!$p||$p['provider']!=='local'||!(int)$p['enabled'])throw new DomainException('Configure a posição como anúncio local ativo antes de ativar.');
                $q=$db->prepare('SELECT id,placement_slug,title,start_date,end_date,active FROM ad_campaigns WHERE placement_slug=? AND active=1 FOR UPDATE');
                $q->execute([$row['placement_slug']]);
                if(igua_ad_overlapping_campaigns($q->fetchAll(),(string)$row['placement_slug'],(string)$row['start_date'],$row['end_date']??null,$id))
                    throw new DomainException('Há outra campanha ativa com datas coincidentes. Edite a campanha e confirme o revezamento.');
            }
            $s=$db->prepare('UPDATE ad_campaigns SET active=1-active WHERE id=?');$s->execute([$id]);
            $db->commit();
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
        flash('Estado da campanha alterado.');redirect('/admin.php?action=ads');
    }
    if($action==='ads_delete_campaign'){
        $id=max(0,(int)($_POST['id']??0));
        $s=$db->prepare('DELETE FROM ad_campaigns WHERE id=?');$s->execute([$id]);
        flash('Campanha removida. Arquivos de imagem antigos não são eliminados automaticamente.');redirect('/admin.php?action=ads');
    }
    throw new RuntimeException('Ação de publicidade desconhecida.');
}
function ad_admin_view(PDO $db): void {
    // adsense-review: a vistoria continua disponível na engrenagem.
    $slots=$db->query('SELECT * FROM ad_placements ORDER BY slug')->fetchAll();
    $campaigns=$db->query('SELECT * FROM ad_campaigns ORDER BY id DESC LIMIT 150')->fetchAll();
    $total=(int)$db->query('SELECT COUNT(*) FROM ad_campaigns')->fetchColumn();
    $demoTotal=igua_ad_demo_count($db);
    $adsOn=setting('ads_enabled','0')==='1';
    $publisher=setting('adsense_publisher','');
    $siteUrl=setting('seo_site_url','');
    try{$siteUrl=safe_seo_url($siteUrl);}catch(Throwable){$siteUrl='';}
    $review=igua_adsense_readiness($db,$publisher,$siteUrl,setting('seo_index','0')==='1');
    $passed=count(array_filter($review,static fn(array $x)=>(bool)$x['ok']));
    $adsTxt=IGUA_PUBLIC.'/ads.txt';
    $rawAdsTxt=(!is_link($adsTxt)&&is_file($adsTxt))?(string)@file_get_contents($adsTxt):'';
    $adsTxtReady=igua_adsense_ads_txt_has_publisher($rawAdsTxt,$publisher);
    // Um erro de validação não deve fazer o administrador perder o formulário.
    $draft=null;
    if(($_SERVER['REQUEST_METHOD']??'')==='POST'&&isset($_POST['title'],$_POST['placement_slug'])) {
        $draft=[];
        foreach(['id','advertiser','title','description','target_url','placement_slug','start_date','end_date','priority'] as $field)
            $draft[$field]=is_string($_POST[$field]??null)?(string)$_POST[$field]:'';
        foreach(['active','prepare_local_slot','allow_overlap'] as $field)$draft[$field]=isset($_POST[$field]);
    }
    require __DIR__.'/admin_views/routes/ads_dashboard.php';
}
