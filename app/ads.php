<?php
declare(strict_types=1);
/** Publicidade independente da redação: todas as posições desligadas por padrão. */
const AD_SLOTS = [
 'p01'=>['Destaque da home','Abaixo da manchete principal'],
 'p02'=>['Entre notícias','Depois da primeira grade da home'],
 'p03'=>['Antes do rodapé','Área institucional inferior'],
 'p04'=>['Dentro da notícia','Após o segundo parágrafo da matéria'],
 'p05'=>['Lateral desktop','Abaixo das mais lidas: lateral desktop ou empilhado no celular'],
 'p06'=>['Horários de ônibus','Após a lista de partidas'],
];
const AD_PROVIDERS = ['off','local','adsense','external'];
function ad_valid_slot(string $slot): bool {return isset(AD_SLOTS[$slot]);}
function ad_validate_publisher(string $publisher): string {
    $publisher=trim($publisher);
    if($publisher!=='' && !preg_match('/^ca-pub-\d{16}$/D',$publisher)) throw new RuntimeException('ID AdSense inválido. Use ca-pub- seguido de 16 números.');
    return $publisher;
}
function ad_validate_url(string $url): string {
    $url=trim($url);
    if(strlen($url)>700 || preg_match('/[\x00-\x20\x7f\\\\]/', $url) || !filter_var($url,FILTER_VALIDATE_URL) || strtolower((string)parse_url($url,PHP_URL_SCHEME))!=='https' || parse_url($url,PHP_URL_USER)!==null || parse_url($url,PHP_URL_PASS)!==null)
        throw new RuntimeException('Use um endereço HTTPS válido, sem usuário ou senha.');
    return $url;
}
function ad_validate_day(string $day, bool $required): ?string {
    $day=trim($day);
    if($day==='' && !$required)return null;
    $d=DateTimeImmutable::createFromFormat('!Y-m-d',$day);
    if(!$d || $d->format('Y-m-d')!==$day)throw new RuntimeException('Data inválida.');
    return $day;
}
function migrate_ads_schema(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS ad_placements (
      slug VARCHAR(16) NOT NULL PRIMARY KEY,
      provider ENUM('off','local','adsense','external') NOT NULL DEFAULT 'off',
      enabled TINYINT(1) NOT NULL DEFAULT 0,
      adsense_slot VARCHAR(32) NOT NULL DEFAULT '',
      external_adapter VARCHAR(50) NOT NULL DEFAULT '',
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS ad_campaigns (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      placement_slug VARCHAR(16) NOT NULL,
      title VARCHAR(130) NOT NULL, advertiser VARCHAR(130) NOT NULL,
      description VARCHAR(220) NOT NULL DEFAULT '', image VARCHAR(150) DEFAULT NULL,
      target_url VARCHAR(700) NOT NULL,
      start_date DATE NOT NULL, end_date DATE DEFAULT NULL,
      priority SMALLINT NOT NULL DEFAULT 0, active TINYINT(1) NOT NULL DEFAULT 0,
      clicks INT UNSIGNED NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_ad_campaign_active (placement_slug,active,start_date,end_date),
      CONSTRAINT fk_ad_campaign_slot FOREIGN KEY (placement_slug) REFERENCES ad_placements(slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $insert=$db->prepare('INSERT IGNORE INTO ad_placements (slug) VALUES (?)');
    foreach(array_keys(AD_SLOTS) as $slug)$insert->execute([$slug]);
    $db->exec("INSERT IGNORE INTO settings(name,value) VALUES ('ads_enabled','0'),('adsense_publisher','')");
}
function ad_slot_settings(PDO $db,string $slot): ?array {
    if(!ad_valid_slot($slot))return null;
    try {$stmt=$db->prepare('SELECT * FROM ad_placements WHERE slug=? LIMIT 1');$stmt->execute([$slot]);return $stmt->fetch()?:null;}
    catch(Throwable){return null;}
}
function ad_active_campaign(PDO $db,string $slot): ?array {
    $stmt=$db->prepare('SELECT * FROM ad_campaigns WHERE placement_slug=? AND active=1 AND start_date<=CURDATE() AND (end_date IS NULL OR end_date>=CURDATE()) ORDER BY priority DESC,id ASC LIMIT 50');
    $stmt->execute([$slot]);$list=$stmt->fetchAll();if(!$list)return null;
    $best=(int)$list[0]['priority'];$list=array_values(array_filter($list,static fn($r)=>(int)$r['priority']===$best));
    // Distribuição diária sem cookies, rastreamento individual ou sorteio de banco.
    $position=(int)(sprintf('%u',crc32($slot.date('Y-m-d')))%count($list));
    return $list[$position];
}
function ad_display_slot(PDO $db,string $slug): void {
    if(!ad_valid_slot($slug) || setting('ads_enabled','0')!=='1')return;
    $slot=ad_slot_settings($db,$slug);
    if(!$slot || !(int)$slot['enabled'])return;
    if($slot['provider']==='local') {
        try {$campaign=ad_active_campaign($db,$slug);}catch(Throwable){return;}
        if(!$campaign)return;
        $label=AD_SLOTS[$slug][0];
        ?>
        <aside class="ad-unit ad-local ad-<?=h($slug)?>" aria-label="Publicidade: <?=h($label)?>">
          <span class="ad-disclosure">Publicidade · <?=h($campaign['advertiser'])?></span>
          <a href="/ad_click.php?id=<?= (int)$campaign['id']?>" rel="sponsored nofollow noopener noreferrer" target="_blank" class="ad-local-card">
            <?php if(!empty($campaign['image'])): ?><img src="<?=h(asset_image($campaign['image']))?>" alt="" loading="lazy"><?php endif; ?>
            <span class="ad-local-copy"><strong><?=h($campaign['title'])?></strong><?php if($campaign['description']!==''):?><small><?=h($campaign['description'])?></small><?php endif;?><span class="ad-action">Conhecer anunciante ↗</span></span>
          </a>
        </aside>
        <?php return;
    }
    if($slot['provider']==='adsense') {
        // Sem anúncios Google no ambiente de testes ou enquanto houver conteúdo demonstrativo.
        if(setting('seo_index','0')!=='1' || (int)($GLOBALS['igua_demo_count']??0)>0 || !in_array((string)($GLOBALS['igua_page_route']??''),['inicio','noticia','categoria','buscar','onibus'],true))return;
        $publisher=setting('adsense_publisher','');$unit=(string)$slot['adsense_slot'];
        if(!preg_match('/^ca-pub-\d{16}$/D',$publisher) || !preg_match('/^\d{6,32}$/D',$unit))return;
        // Não carrega bibliotecas externas até haver escolha explícita de consentimento.
        ?><aside class="ad-unit ad-google ad-<?=h($slug)?>" data-ad-publisher="<?=h($publisher)?>" data-ad-unit="<?=h($unit)?>" aria-label="Publicidade Google"><span class="ad-disclosure">Publicidade</span><div class="ad-google-mount"></div></aside><?php
    }
    // Redes externas são implementadas por adaptadores específicos verificados.
    // Nenhum JavaScript/HTML enviado pelo painel é executado diretamente.
}
function ad_article_body(PDO $db,string $body): void {
    $parts=array_values(array_filter(preg_split('/\R\s*\R/u',trim($body))?:[],static fn($p)=>trim($p)!==''));
    foreach($parts as $i=>$paragraph) {
        echo news_text_format($paragraph);
        if($i===1)ad_display_slot($db,'p04');
    }
    if(count($parts)<2)ad_display_slot($db,'p04');
}
