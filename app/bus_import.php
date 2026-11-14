<?php
declare(strict_types=1);
/** IguaNews: adaptadores conservadores para arquivos oficiais ANTT e DER-MG.
 *  NUNCA inventa minutos intermediários ou horários de chegada.
 *  Todo registro passa pela revisão do administrador antes de publicar.
 */
require_once __DIR__.'/bus.php';
const IGUA_ANTT_CATALOG = 'https://dados.antt.gov.br/dataset/gerenciamento-de-autorizacoes';
const IGUA_DER_CATALOG = 'https://www.consultas.der.mg.gov.br/grgx/sgti/acesso_informacao.xhtml';

function bus_import_normalize(string $s): string {
    $s=trim(preg_replace('/\s+/u',' ',$s)??$s);
    if(!preg_match('//u',$s)) $s=iconv('Windows-1252','UTF-8//IGNORE',$s) ?: $s;
    return trim($s);
}
/** TXT oficial DER pode vir separado por ;, tab, barra vertical ou blocos de espaços. */
function bus_import_csv_rows(string $path,int $maxRows=50000): array {
    if(!is_file($path) || filesize($path)>15*1024*1024)throw new RuntimeException('Arquivo excede 15 MB ou não foi encontrado.');
    $file=fopen($path,'rb');if(!$file)throw new RuntimeException('Não foi possível abrir o arquivo.');
    try{
        $preview=[];
        for($i=0;$i<35 && ($line=fgets($file))!==false;$i++){
            $line=rtrim(preg_replace('/^\xEF\xBB\xBF/','',$line),"\r\n");
            if(trim($line)!=='' && !preg_match('/^[-=| +]+$/',$line))$preview[]=$line;
        }
        $header='';$separator=';';$spaces=false;$headers=[];
        foreach($preview as $line){
            $v=preg_match('//u',$line) ? $line : (iconv('Windows-1252','UTF-8//IGNORE',$line) ?: $line);
            foreach([';',"\t",'|',','] as $candidate){
                $columns=str_getcsv($v,$candidate,'"','');
                $slugs=array_map('bus_import_header',$columns);
                $known=count(array_intersect($slugs,['cod_linh','nome_linh','hora_partida','prefixo','descricao_linha','horario']));
                if(count($slugs)>2 && $known>=2){$header=$line;$separator=$candidate;$headers=$slugs;break 2;}
            }
            if(preg_match('/\s{2,}/',$line)){
                $slugs=array_map('bus_import_header',preg_split('/\s{2,}/',$line));
                if(count(array_intersect($slugs,['cod_linh','nome_linh','hora_partida']))>=2){$header=$line;$headers=$slugs;$spaces=true;break;}
            }
        }
        if(!$header)throw new RuntimeException('O TXT não contém cabeçalho de quadro de horários reconhecível. Envie quadro_horario_inter.txt (não itinerario_inter.txt ou pontos de parada). Confira COD_LINH, NOME_LINH e HORA_PARTIDA.');
        rewind($file);$found=false;$rows=[];
        while(($line=fgets($file))!==false){
            $line=rtrim(preg_replace('/^\xEF\xBB\xBF/','',$line),"\r\n");
            if(!$found){if($line===$header)$found=true;continue;}
            if(trim($line)==='' || preg_match('/^[-=| +]+$/',$line))continue;
            $cells=$spaces?preg_split('/\s{2,}/',$line):str_getcsv($line,$separator,'"','');
            if(!is_array($cells) || count($cells)!==count($headers))continue;
            if(count($rows)>=$maxRows)throw new RuntimeException('Arquivo com mais de '.$maxRows.' registros. Filtre o arquivo antes da importação.');
            $rows[]=array_combine($headers,array_map('bus_import_normalize',$cells));
        }
        if(!$rows)throw new RuntimeException('Nenhum registro legível encontrado. Verifique o arquivo TXT original do DER-MG ou CSV da ANTT.');
        return $rows;
    }finally{fclose($file);}
}
function bus_import_header(string $header): string {
    $header=preg_replace('/^\xEF\xBB\xBF/','',trim($header));
    $header=bus_import_normalize($header);
    $ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$header)?:$header;
    return strtolower(trim(preg_replace('/[^a-zA-Z0-9_]+/','_',$ascii),'_'));
}
function bus_import_mask(array $r,string $source): int {
    $days= $source==='der_mg' ? ['domingo','segunda','terca','quarta','quinta','sexta','sabado'] : ['domingo','segunda_feira','terca_feira','quarta_feira','quinta_feira','sexta_feira','sabado'];
    $mask=0;
    foreach($days as $i=>$field){$v=strtolower(trim((string)($r[$field]??'')));if(in_array($v,['x','1','s','sim','true','yes','*'],true))$mask|=1<<$i;}
    return $mask; // ausência de dias NÃO significa diariamente
}
function bus_import_time(string $value): ?string {
    $value=str_replace(['h','H'],':',trim($value));
    if(preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/D',$value,$m)){
        $t=sprintf('%02d:%s',(int)$m[1],$m[2]);return bus_valid_time($t)?$t:null;
    }
    return null;
}
/** Detecta apenas separadores inequívocos entre localidades. Prefixos e siglas são preservados. */
function bus_import_endpoints(string $label): array {
    $label=bus_import_normalize($label);
    $parts=preg_split('/\s+(?:-|–|→|X|x|PARA|para)\s+|\s*\/\s*|\s*→\s*/u',$label);
    if(count($parts)!==2)return [];
    $out=[];
    foreach($parts as $city){$city=trim(preg_replace('/\s*\([A-Z]{2}\)\s*$/','',$city)??$city);if(strlen($city)<2||mb_bus_len($city)>100)return [];$out[]=$city;}
    if(strcasecmp($out[0],$out[1])===0)return [];
    return $out;
}
function mb_strtolower_safe(string $s): string {return function_exists('mb_strtolower')?mb_strtolower(trim($s),'UTF-8'):strtolower(trim($s));}
function bus_import_city_match(array $cities,string $filter): bool {
    if($filter==='')return true;
    $simplify=static fn($v)=>strtolower(iconv('UTF-8','ASCII//TRANSLIT//IGNORE',(string)$v)?:$v);
    return (bool)array_filter($cities,static fn($city)=>str_contains($simplify($city),$simplify($filter)));
}
/** Processa os dois formatos documentados. Por segurança, só cria trajetos de extremidade.
 *  O DER pode incluir itinerário separadamente, mas sem TEMPO por parada, não publica previsões intermediárias.
 */
function bus_import_build_candidates(string $source,array $rows,string $filter=''): array {
    if(!in_array($source,['antt','der_mg'],true))throw new InvalidArgumentException('Fonte inválida.');
    if(!$rows)throw new RuntimeException('Arquivo vazio.');
    $mandatory=$source==='antt'?['prefixo','descricao_linha','horario','sentido']:['cod_linh','nome_linh','hora_partida'];
    $columns=array_keys($rows[0]);
    foreach($mandatory as $required)if(!in_array($required,$columns,true))throw new RuntimeException('Coluna obrigatória ausente: '.$required.'. Confirme o arquivo oficial do tipo selecionado.');
    $groups=[];$skipped=['no_cities'=>0,'no_days'=>0,'invalid_time'=>0];
    foreach($rows as $row){
        if($source==='antt'){
            $line=(string)($row['descricao_linha']??'');$prefix=(string)($row['prefixo']??'');$direction=strtolower((string)($row['sentido']??''));
            $time=(string)($row['horario']??'');$company=(string)($row['razao_social']??'');
            if(!$line||!$prefix)continue;
            $cities=bus_import_endpoints($line);
            if(in_array($direction,['volta','retorno'],true) && count($cities)===2)$cities=array_reverse($cities);
            $key=$prefix.'|'.($direction?:'ida');
        }else{
            $line=(string)($row['nome_linh']??'');$prefix=(string)($row['cod_linh']??'');
            $trajeto=(string)($row['trajeto']??'');$time=(string)($row['hora_partida']??'');$company='';
            if(!$line||!$prefix)continue;
            $cities=bus_import_endpoints($trajeto) ?: bus_import_endpoints($line);
            if(count($cities)===2 && mb_strtolower_safe($trajeto)==='volta')$cities=array_reverse($cities);
            $key=$prefix.'|'.($trajeto?:'regular');
        }
        if(count($cities)!==2){$skipped['no_cities']++;continue;}
        if(!bus_import_city_match($cities,$filter))continue;
        $clock=bus_import_time($time);if($clock===null){$skipped['invalid_time']++;continue;}
        $mask=bus_import_mask($row,$source);if($mask===0){$skipped['no_days']++;continue;}
        $key=substr(hash('sha256',$key.'|'.implode('|',$cities)),0,48);
        if(!isset($groups[$key]))$groups[$key]=['name'=>implode(' → ',$cities),'company'=>substr($company,0,160),'cities'=>$cities,'departures'=>[],'external_key'=>$key,'source'=>$source];
        $id=$clock.'-'.$mask;$groups[$key]['departures'][$id]=['time'=>$clock,'days_mask'=>$mask];
    }
    foreach($groups as &$group){$group['departures']=array_values($group['departures']);}unset($group);
    return ['groups'=>array_values($groups),'skipped'=>$skipped];
}
function bus_import_stage(PDO $db,string $source,array $groups,string $sourceUrl): array {
    $created=0;$updated=0;$fixed=0;
    $own=!$db->inTransaction();if($own)$db->beginTransaction();
    try{
    $read=$db->prepare('SELECT id FROM bus_import_queue WHERE source=? AND external_key=?');
    $upsert=$db->prepare("INSERT INTO bus_import_queue(source,external_key,payload,source_url,status,created_at) VALUES (?,?,?,?,'pending',NOW()) ON DUPLICATE KEY UPDATE payload=VALUES(payload),source_url=VALUES(source_url),status='pending',created_at=NOW()");
    $protected=$db->prepare('SELECT id,sync_mode FROM bus_routes WHERE source_type=? AND source_key=?');
    foreach($groups as $g){
        $protected->execute([$source,$g['external_key']]);$route=$protected->fetch(PDO::FETCH_ASSOC);
        if($route && $route['sync_mode']==='fixed'){$fixed++;continue;}
        $read->execute([$source,$g['external_key']]);$present=(bool)$read->fetchColumn();
        $upsert->execute([$source,$g['external_key'],json_encode($g,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$sourceUrl]);
        $present?$updated++:$created++;
    }
    if($own)$db->commit();
    return compact('created','updated','fixed');
    }catch(Throwable $e){if($own && $db->inTransaction())$db->rollBack();throw $e;}
}
function bus_import_approve(PDO $db,int $queueId): string {
    $st=$db->prepare('SELECT * FROM bus_import_queue WHERE id=? AND status=?');$st->execute([$queueId,'pending']);$candidate=$st->fetch(PDO::FETCH_ASSOC);
    if(!$candidate)throw new RuntimeException('Registro pendente não encontrado.');
    $g=json_decode($candidate['payload'],true,64,JSON_THROW_ON_ERROR);
    if(!is_array($g)||count($g['cities']??[])!==2||!count($g['departures']??[]))throw new RuntimeException('Registro importado incompleto.');
    $source=$candidate['source'];$key=$candidate['external_key'];
    $db->beginTransaction();
    try{
        $s=$db->prepare('SELECT id,sync_mode FROM bus_routes WHERE source_type=? AND source_key=? FOR UPDATE');$s->execute([$source,$key]);$route=$s->fetch();
        if($route && $route['sync_mode']==='fixed')throw new RuntimeException('Linha fixa: atualizações externas bloqueadas.');
        if($route){
            $rid=(int)$route['id'];
            $db->prepare('UPDATE bus_routes SET name=?,company=?,source_url=?,last_source_update=NOW() WHERE id=?')->execute([substr($g['name'],0,160),substr($g['company'],0,160),$candidate['source_url'],$rid]);
            $db->prepare('DELETE FROM bus_departures WHERE route_id=?')->execute([$rid]);
            $db->prepare('DELETE FROM bus_stops WHERE route_id=?')->execute([$rid]);
        }else{
            $db->prepare("INSERT INTO bus_routes(name,company,active,source_type,source_key,source_url,sync_mode,last_source_update,notes) VALUES(?,?,?,?,?,?,'auto',NOW(),?)")
                ->execute([substr($g['name'],0,160),substr($g['company'],0,160),1,$source,$key,$candidate['source_url'],'Horários da fonte oficial; confira a operação diretamente com a empresa.']);
            $rid=(int)$db->lastInsertId();
        }
        // Sem dados oficiais de duração, mantém minutos da última parada NULL.
        $ins=$db->prepare('INSERT INTO bus_stops(route_id,city,location_label,stop_order,minutes_from_start) VALUES (?,?,?,?,?)');
        $ins->execute([$rid,$g['cities'][0],'',1,0]);$ins->execute([$rid,$g['cities'][1],'',2,null]);
        $time=$db->prepare('INSERT INTO bus_departures(route_id,start_time,days_mask,active) VALUES(?,?,?,1)');
        foreach($g['departures'] as $d){if(!bus_valid_time((string)$d['time']) || (int)$d['days_mask']<1||(int)$d['days_mask']>127)throw new RuntimeException('Horário inválido no arquivo.');$time->execute([$rid,$d['time'],(int)$d['days_mask']]);}
        $db->prepare("UPDATE bus_import_queue SET status='approved' WHERE id=?")->execute([$queueId]);
        $db->commit();return 'Linha oficial aprovada. Ajustes manuais podem ser protegidos como Linha fixa.';
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
/** Apenas downloads HTTPS do catálogo ANTT; jamais permitir URL arbitrária ou rede interna. */
function bus_import_remote_antt(): string {
    $url='https://dados.antt.gov.br/dataset/a8729428-f382-430c-abe5-6e5f85aa9a03/resource/079bd90a-efd9-466c-adc0-c8bf82a69d0d/download/09-2026_horarios_sigma.csv';
    if(!function_exists('curl_init'))throw new RuntimeException('Instale a extensão PHP cURL ou baixe o CSV oficial e envie pelo formulário.');
    $f=tempnam(sys_get_temp_dir(),'antt_');if(!$f)throw new RuntimeException('Falha no arquivo temporário.');
    $handle=fopen($f,'wb');if(!$handle)throw new RuntimeException('Não foi possível abrir arquivo temporário.');
    $ch=curl_init($url);
    curl_setopt_array($ch,[CURLOPT_FILE=>$handle,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>45,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_USERAGENT=>'IguaNews/1.0.8 dados-publicos',CURLOPT_NOPROGRESS=>false,CURLOPT_PROGRESSFUNCTION=>static function($resource,$total,$downloaded) {return $downloaded>12*1024*1024?1:0;}]);
    $ok=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);fclose($handle);
    if(!$ok || $status!==200){@unlink($f);throw new RuntimeException('Download não concluído (HTTP '.$status.'). '.$err.' Utilize o envio manual do CSV.');}
    return $f;
}
