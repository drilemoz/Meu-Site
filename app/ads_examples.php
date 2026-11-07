<?php
declare(strict_types=1);

const AD_DEMO_URL_PREFIX = 'https://example.com/iguanews-demo/';

/** @return array<int,array<string,mixed>> */
function ad_demo_campaigns(): array {
    $today=(new DateTimeImmutable('today'))->format('Y-m-d');
    return [
        ['placement_slug'=>'p01','title'=>'EXEMPLO FICTÍCIO · Padaria Central','advertiser'=>'DEMO '.igua_brand_name().' · Padaria Central','description'=>'Banner de teste para o topo da home com promoção fictícia de café e pão de queijo.','target_url'=>AD_DEMO_URL_PREFIX.'p01-topo-home','start_date'=>$today,'end_date'=>null,'priority'=>90,'active'=>1,'image'=>null],
        ['placement_slug'=>'p02','title'=>'EXEMPLO FICTÍCIO · Farmácia Bom Preço','advertiser'=>'DEMO '.igua_brand_name().' · Farmácia Bom Preço','description'=>'Campanha de demonstração entre notícias, simulando ofertas sazonais de higiene e bem-estar.','target_url'=>AD_DEMO_URL_PREFIX.'p02-entre-noticias','start_date'=>$today,'end_date'=>null,'priority'=>80,'active'=>1,'image'=>null],
        ['placement_slug'=>'p03','title'=>'EXEMPLO FICTÍCIO · Supermercado Avenida','advertiser'=>'DEMO '.igua_brand_name().' · Supermercado Avenida','description'=>'Exemplo de publicidade antes do rodapé com chamada curta e link patrocinado.','target_url'=>AD_DEMO_URL_PREFIX.'p03-rodape','start_date'=>$today,'end_date'=>null,'priority'=>70,'active'=>1,'image'=>null],
        ['placement_slug'=>'p04','title'=>'EXEMPLO FICTÍCIO · Oficina União','advertiser'=>'DEMO '.igua_brand_name().' · Oficina União','description'=>'Exemplo para o corpo da notícia após o segundo parágrafo, sem imitar botões editoriais.','target_url'=>AD_DEMO_URL_PREFIX.'p04-corpo-noticia','start_date'=>$today,'end_date'=>null,'priority'=>60,'active'=>1,'image'=>null],
        ['placement_slug'=>'p05','title'=>'EXEMPLO FICTÍCIO · Loja da Praça','advertiser'=>'DEMO '.igua_brand_name().' · Loja da Praça','description'=>'Posição de teste para lateral desktop e empilhamento no celular.','target_url'=>AD_DEMO_URL_PREFIX.'p05-lateral','start_date'=>$today,'end_date'=>null,'priority'=>50,'active'=>1,'image'=>null],
        ['placement_slug'=>'p06','title'=>'EXEMPLO FICTÍCIO · Lanchonete Rodoviária','advertiser'=>'DEMO '.igua_brand_name().' · Lanchonete Rodoviária','description'=>'Anúncio demonstrativo abaixo dos horários de ônibus, útil para validar o espaço sem dados reais.','target_url'=>AD_DEMO_URL_PREFIX.'p06-onibus','start_date'=>$today,'end_date'=>null,'priority'=>40,'active'=>1,'image'=>null],
    ];
}
function ad_is_demo_campaign(array $campaign): bool {
    $target=(string)($campaign['target_url'] ?? '');
    return str_starts_with($target, AD_DEMO_URL_PREFIX);
}
function ad_assert_demo_seed_safe(PDO $db):void {
    // Nunca alterar uma posição já reservada ao AdSense ou com publicidade real.
    $real=$db->prepare('SELECT COUNT(*) FROM ad_campaigns WHERE target_url NOT LIKE ?');
    $real->execute([AD_DEMO_URL_PREFIX.'%']);
    if((int)$real->fetchColumn()>0) throw new DomainException('Já existem campanhas reais. Remova ou gerencie os exemplos manualmente; a geração em lote não altera posições de clientes.');
    $reserved=(int)$db->query("SELECT COUNT(*) FROM ad_placements WHERE provider IN ('adsense','external') AND enabled=1")->fetchColumn();
    if($reserved>0) throw new DomainException('Há posições reservadas ao AdSense ou a outra rede. A geração de exemplos não as substituirá.');
}
/** @return array{inserted:int,enabled_slots:int,activated_general:bool} */
function ad_seed_demo_campaigns(PDO $db): array {
    migrate_ads_schema($db);
    ad_assert_demo_seed_safe($db);
    $created=0;$slots=0;$ownTx=!$db->inTransaction();if($ownTx)$db->beginTransaction();
    try {
        $db->query('SELECT slug FROM ad_placements ORDER BY slug FOR UPDATE')->fetchAll();
        ad_assert_demo_seed_safe($db); // revalidar sob bloqueio contra outra sessão
        $db->prepare('DELETE FROM ad_campaigns WHERE target_url LIKE ?')->execute([AD_DEMO_URL_PREFIX.'%']);
        $insert=$db->prepare('INSERT INTO ad_campaigns (placement_slug,title,advertiser,description,image,target_url,start_date,end_date,priority,active) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $saveSlot=$db->prepare("UPDATE ad_placements SET provider='local', enabled=1, adsense_slot='', external_adapter='' WHERE slug=?");
        foreach(ad_demo_campaigns() as $row){
            $saveSlot->execute([(string)$row['placement_slug']]);
            $insert->execute([$row['placement_slug'],$row['title'],$row['advertiser'],$row['description'],$row['image'],$row['target_url'],$row['start_date'],$row['end_date'],$row['priority'],$row['active']]);
            $created++;$slots++;
        }
        $db->prepare("INSERT INTO settings (name,value) VALUES ('ads_enabled','1') ON DUPLICATE KEY UPDATE value='1'")->execute();
        if($ownTx)$db->commit();
        return ['inserted'=>$created,'enabled_slots'=>$slots,'activated_general'=>true];
    } catch(Throwable $e){if($ownTx && $db->inTransaction())$db->rollBack();throw $e;}
}
