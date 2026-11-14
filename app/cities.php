<?php
declare(strict_types=1);
/** Catálogo de municípios: IBGE, armazenado em cache privado; sem API key. */
function city_clean(string $value): string {
    $t=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value) ?: $value;
    return strtolower($t);
}
function cities_catalog_file(): string {return __DIR__.'/../storage/municipios_ibge.json';}
function cities_fetch_ibge(): array {
    $url='https://servicodados.ibge.gov.br/api/v1/localidades/municipios?view=nivelado';
    if (!function_exists('curl_init')) return [];
    $ch=curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>9,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_USERAGENT=>'IguaNews/1.0.8 IBGE-localidades',CURLOPT_NOPROGRESS=>false,CURLOPT_PROGRESSFUNCTION=>static function($r,$total,$downloaded){return $downloaded>5*1024*1024?1:0;}]);
    $raw=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
    if($status!==200 || !is_string($raw) || strlen($raw)>5*1024*1024)return [];
    $data=json_decode($raw,true,64);if(!is_array($data))return [];
    $out=[];
    foreach($data as $city){
        $name=trim((string)($city['nome']??''));
        $uf=(string)($city['UF-sigla']??$city['microrregiao']['mesorregiao']['UF']['sigla']??$city['regiao-imediata']['regiao-intermediaria']['UF']['sigla']??'');
        if($name!=='' && mb_bus_len($name)<=100 && preg_match('/^[A-Z]{2}$/',$uf))$out[]=['name'=>$name,'uf'=>$uf];
    }
    return count($out)>5000 ? $out : []; // impede gravar resposta HTML/erro/coleção parcial
}
function cities_cached(): array {
    $file=cities_catalog_file();$cached=[];
    $failure=$file.'.last-failure';
    if(is_file($file)){$raw=@file_get_contents($file);$decoded=json_decode((string)$raw,true);if(is_array($decoded))$cached=$decoded;}
    if($cached && (time()-(int)@filemtime($file))<30*86400)return $cached;
    // Se o IBGE estiver offline, não bloquear cada tecla do usuário com novos timeouts.
    if(is_file($failure) && time()-(int)@filemtime($failure)<3600)return $cached;
    $fresh=cities_fetch_ibge();
    if($fresh){@unlink($failure);$json=json_encode($fresh,JSON_UNESCAPED_UNICODE);if($json!==false){$temp=$file.'.'.bin2hex(random_bytes(4)).'.tmp';if(@file_put_contents($temp,$json,LOCK_EX)!==false)@rename($temp,$file);else @unlink($temp);}return $fresh;}
    @touch($failure);return $cached;
}
function cities_suggest(PDO $db,string $query,int $limit=18): array {
    $query=trim($query); if(strlen($query)>120) return [];
    $matches=[];$term=city_clean($query);
    $official=cities_cached();
    foreach($official as $row){
        $name=(string)($row['name']??'');$uf=(string)($row['uf']??'');
        if($name==='' || ($term!=='' && !str_contains(city_clean($name),$term)))continue;
        $matches[$name]=['name'=>$name,'uf'=>$uf,'source'=>'IBGE'];

    }
    $st=$db->query('SELECT DISTINCT city FROM bus_stops LIMIT 5000');
    foreach($st->fetchAll(PDO::FETCH_COLUMN) as $registered){$name=(string)$registered;if($term!=='' && !str_contains(city_clean($name),$term))continue;$matches[$name]=['name'=>$name,'uf'=>$matches[$name]['uf']??'','source'=>'Portal'];}
    foreach(['Iguatama','Arcos','Formiga','Piumhi','Uberlândia','Belo Horizonte','Garças de Minas'] as $name){if($term!=='' && !str_contains(city_clean($name),$term))continue;if(!isset($matches[$name]))$matches[$name]=['name'=>$name,'uf'=>'MG','source'=>'Referência local'];}
    $items=array_values($matches);
    usort($items,static function($a,$b) use ($term){$aStart=str_starts_with(city_clean($a['name']),$term);$bStart=str_starts_with(city_clean($b['name']),$term);return ($bStart<=>$aStart) ?: strcmp(city_clean($a['name']),city_clean($b['name']));});
    return array_slice($items,0,$limit);
}
