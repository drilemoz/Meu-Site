<?php
declare(strict_types=1);
/**
 * Consulta sob demanda à Google Analytics Data API, usando conta de serviço.
 * Credenciais nunca são incluídas em HTML, SQL, relatórios ou logs.
 */
function igua_ga4_property_id(string $value): string {
    $value=trim($value);
    if ($value!=='' && !preg_match('/^[1-9][0-9]{3,19}$/D',$value)) {
        throw new InvalidArgumentException('Informe o ID numérico da propriedade GA4 (não o G-...).');
    }
    return $value;
}
function igua_ga4_credentials_path(): string {
    return IGUA_STORAGE.'/integrations/ga4-service-account.json';
}
/** @return array<string,mixed> */
function igua_ga4_decode_service_account(string $raw): array {
    if(strlen($raw)<200 || strlen($raw)>32768) throw new InvalidArgumentException('JSON de conta de serviço inválido ou muito grande (máximo 32 KiB).');
    try{$data=json_decode($raw,true,16,JSON_THROW_ON_ERROR);}catch(JsonException){throw new InvalidArgumentException('O arquivo não contém JSON válido.');}
    if(!is_array($data) || ($data['type']??'')!=='service_account'
        || ($data['token_uri']??'')!=='https://oauth2.googleapis.com/token'
        || !is_string($data['client_email']??null)
        || strlen($data['client_email'])>254
        || !preg_match('/^[a-zA-Z0-9._+-]+@[a-zA-Z0-9.-]+\.gserviceaccount\.com$/D',$data['client_email'])
        || !is_string($data['private_key']??null)) {
        throw new InvalidArgumentException('Use o JSON de uma conta de serviço Google Cloud válida.');
    }
    if(!function_exists('openssl_pkey_get_private'))throw new RuntimeException('Extensão OpenSSL necessária na hospedagem.');
    $key=@openssl_pkey_get_private($data['private_key']);
    if($key===false)throw new InvalidArgumentException('Chave privada inválida no arquivo JSON.');
    $details=openssl_pkey_get_details($key);
    if(!is_array($details) || ($details['type']??null)!==OPENSSL_KEYTYPE_RSA || ($details['bits']??0)<2048)
        throw new InvalidArgumentException('O JSON exige uma chave RSA segura de conta de serviço.');
    // Não reter nem gravar atributos estranhos fornecidos no JSON.
    return ['type'=>'service_account','client_email'=>$data['client_email'],
        'private_key'=>$data['private_key'],'token_uri'=>'https://oauth2.googleapis.com/token'];
}
function igua_ga4_read_service_account(string $path): ?array {
    if(is_link($path))throw new RuntimeException('Credencial configurada por link simbólico: acesso recusado.');
    if(!is_file($path))return null;
    $size=@filesize($path);
    if($size===false || $size>32768)throw new RuntimeException('Credencial privada inválida.');
    $raw=@file_get_contents($path);
    if(!is_string($raw))throw new RuntimeException('Sem permissão para ler a credencial privada.');
    return igua_ga4_decode_service_account($raw);
}
function igua_ga4_store_service_account(string $raw,string $path): void {
    $creds=igua_ga4_decode_service_account($raw);
    $directory=dirname($path);
    if(is_link($directory))throw new RuntimeException('Diretório de credenciais inválido.');
    if(!is_dir($directory) && !@mkdir($directory,0700,true))throw new RuntimeException('Não foi possível criar armazenamento privado.');
    if(!@chmod($directory,0700))throw new RuntimeException('Não foi possível restringir o diretório de credenciais.');
    if(is_link($path))throw new RuntimeException('Arquivo de credenciais inválido.');
    $tmp=$directory.'/.ga4-'.bin2hex(random_bytes(10)).'.tmp';
    try {
        $handle=@fopen($tmp,'x');if(!$handle)throw new RuntimeException('Falha na gravação privada da credencial.');
        @chmod($tmp,0600);
        $data=json_encode($creds,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        try{
            if(fwrite($handle,$data)!==strlen($data) || !fflush($handle))throw new RuntimeException('Gravação incompleta da credencial.');
        }finally{fclose($handle);}
        if(!@rename($tmp,$path))throw new RuntimeException('Falha ao ativar a credencial.');
        @chmod($path,0600);
    }finally{if(is_file($tmp))@unlink($tmp);}
}
function igua_ga4_delete_service_account(string $path): void {
    if(is_link($path))throw new RuntimeException('Arquivo de credencial inválido.');
    if(is_file($path) && !@unlink($path))throw new RuntimeException('Não foi possível remover a credencial.');
}
/** Resposta HTTP com tamanho e tempo limitados, sem redirecionamento e hosts fixos. */
function igua_ga4_google_post(string $url,array $headers,array $body,bool $form=false): array {
    if(!function_exists('curl_init'))throw new RuntimeException('A extensão cURL está indisponível na hospedagem.');
    $allowed=$url==='https://oauth2.googleapis.com/token'
        || preg_match('~^https://analyticsdata\.googleapis\.com/v1beta/properties/[1-9][0-9]{3,19}:(?:run(?:Realtime)?Report|batchRunReports)$~D',$url);
    if(!$allowed)throw new RuntimeException('Endpoint da API não permitido.');
    $payload=$form?http_build_query($body,'','&',PHP_QUERY_RFC3986):json_encode($body,JSON_THROW_ON_ERROR);
    $ch=curl_init($url);
    if($ch===false)throw new RuntimeException('Não foi possível iniciar a conexão HTTPS.');
    $response='';$overflow=false;
    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_HTTPHEADER=>array_merge(
        [$form?'Content-Type: application/x-www-form-urlencoded':'Content-Type: application/json','Accept: application/json'], $headers),
        CURLOPT_RETURNTRANSFER=>false,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>5,
        CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,
        CURLOPT_WRITEFUNCTION=>static function($curl,string $chunk)use(&$response,&$overflow):int{
            if(strlen($response)+strlen($chunk)>131072){$overflow=true;return 0;}
            $response.=$chunk;return strlen($chunk);
        }]);
    $ok=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
    if($overflow)throw new RuntimeException('Resposta excessiva da API Google.');
    if($ok===false)throw new RuntimeException('Conexão HTTPS com o Google indisponível. Confira rede e certificados do servidor.');
    return ['status'=>$status,'body'=>$response];
}
function igua_ga4_google_response(array $response,string $stage): array {
    $status=(int)($response['status']??0);
    $data=json_decode((string)($response['body']??''),true);
    if($status===401)throw new RuntimeException('Google recusou a autenticação. Reconfigure a conta de serviço.');
    if($status===403)throw new RuntimeException('A API recusou o acesso (403). Ative a Analytics Data API e dê à conta de serviço acesso de Leitor à propriedade.');
    if($status===404)throw new RuntimeException('Propriedade GA4 não encontrada (404). Confira o ID numérico.');
    if($status===429)throw new RuntimeException('Limite de consultas da API excedido. Teste novamente mais tarde.');
    if($status<200 || $status>=300)throw new RuntimeException('API Google indisponível ou recusou a consulta (HTTP '.$status.', etapa '.$stage.').');
    if(!is_array($data))throw new RuntimeException('Resposta inesperada da API Google.');
    return $data;
}
function igua_ga4_last_event_date(array $response): string {
    $dateColumn=array_search('date',array_column($response['dimensionHeaders']??[],'name'),true);
    $eventsColumn=array_search('eventCount',array_column($response['metricHeaders']??[],'name'),true);
    if($dateColumn===false || $eventsColumn===false)return '';
    $last='';
    foreach(($response['rows']??[]) as $row){
        $date=$row['dimensionValues'][$dateColumn]['value']??'';
        $events=$row['metricValues'][$eventsColumn]['value']??'0';
        if(is_string($date) && preg_match('/^20[0-9]{6}$/D',$date) && (int)$events>0 && strcmp($date,$last)>0)$last=$date;
    }
    return $last!==''?substr($last,6,2).'/'.substr($last,4,2).'/'.substr($last,0,4):'';
}
function igua_ga4_metric(array $response,string $name): int {
    $headers=array_column($response['metricHeaders']??[],'name');
    $index=array_search($name,$headers,true);
    if($index===false)return 0;
    $sum=0;
    foreach(($response['rows']??[]) as $row){
        $raw=$row['metricValues'][$index]['value']??'0';
        if(is_string($raw) && preg_match('/^[0-9]+$/D',$raw))$sum+=(int)$raw;
    }
    return $sum;
}
/** OAuth JWT de curta duração. O token jamais deve ser guardado em sessão ou exibido no HTML. */
function igua_ga4_access_token(array $credentials,callable $transport,int $now): string {
    $credentials=igua_ga4_decode_service_account(json_encode($credentials,JSON_THROW_ON_ERROR));
    $header=rtrim(strtr(base64_encode(json_encode(['alg'=>'RS256','typ'=>'JWT'],JSON_THROW_ON_ERROR)),'+/','-_'),'=');
    $claims=['iss'=>$credentials['client_email'],'scope'=>'https://www.googleapis.com/auth/analytics.readonly',
        'aud'=>'https://oauth2.googleapis.com/token','iat'=>$now-30,'exp'=>$now+3300];
    $payload=rtrim(strtr(base64_encode(json_encode($claims,JSON_THROW_ON_ERROR)),'+/','-_'),'=');
    $input=$header.'.'.$payload;$signature='';
    if(!openssl_sign($input,$signature,$credentials['private_key'],OPENSSL_ALGO_SHA256))throw new RuntimeException('Falha ao assinar a autenticação Google.');
    $jwt=$input.'.'.rtrim(strtr(base64_encode($signature),'+/','-_'),'=');
    $auth=igua_ga4_google_response($transport('https://oauth2.googleapis.com/token',[],
        ['grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer','assertion'=>$jwt],true),'autenticação');
    $token=$auth['access_token']??null;
    if(!is_string($token) || !preg_match('/^[a-zA-Z0-9._~+\/-]{10,4096}$/D',$token))
        throw new RuntimeException('Google não forneceu um token de acesso válido.');
    return $token;
}

/**
 * Teste explicitamente acionado pelo admin. $transport injetável permite testes offline.
 * Os totais pertencem à propriedade, não provam que o stream G- do portal gerou os eventos.
 */
function igua_ga4_test_receiving(array $credentials,string $property,?callable $transport=null,?int $now=null): array {
    $property=igua_ga4_property_id($property);
    if($property==='')throw new InvalidArgumentException('Informe o ID numérico da propriedade GA4.');
    $transport=$transport??'igua_ga4_google_post';$now=$now??time();
    $token=igua_ga4_access_token($credentials,$transport,$now);
    $endpoint='https://analyticsdata.googleapis.com/v1beta/properties/'.$property;
    $headers=['Authorization: Bearer '.$token];
    $live=igua_ga4_google_response($transport($endpoint.':runRealtimeReport',$headers,
        ['metrics'=>[['name'=>'eventCount'],['name'=>'activeUsers']]],false),'tempo real');
    $recent=igua_ga4_google_response($transport($endpoint.':runReport',$headers,
        ['dateRanges'=>[['startDate'=>'6daysAgo','endDate'=>'today']], 'dimensions'=>[['name'=>'date']], 'metrics'=>[['name'=>'eventCount']]],false),'últimos 7 dias');
    $liveEvents=igua_ga4_metric($live,'eventCount');$liveUsers=igua_ga4_metric($live,'activeUsers');
    $recentEvents=igua_ga4_metric($recent,'eventCount');
    return ['status'=>$liveEvents>0?'realtime':($recentEvents>0?'recent':'empty'),
        'realtime_events'=>$liveEvents,'realtime_users'=>$liveUsers,'recent_events'=>$recentEvents,
        'property_id'=>$property,'last_event_date'=>igua_ga4_last_event_date($recent), 'checked_at'=>date('d/m/Y H:i:s',$now)];
}

/** Valores inteiros não negativos em respostas GA4, limitados para evitar overflow e conteúdo inesperado. */
function igua_ga4_safe_number(mixed $value): ?int {
    if(!is_string($value) || !preg_match('/^[0-9]{1,15}$/D',$value))return null;
    return (int)$value;
}
function igua_ga4_report_metric(array $response,array $row,string $metric): ?int {
    $headers=array_column($response['metricHeaders']??[],'name');
    $index=array_search($metric,$headers,true);
    return $index===false?null:igua_ga4_safe_number($row['metricValues'][$index]['value']??null);
}
/** Não retorna conteúdo bruto arbitrário da API nem endereços fornecidos por usuários. */
function igua_ga4_report_rows(array $report,string $dimension,string $metric,int $limit=10): array {
    $headers=array_column($report['dimensionHeaders']??[],'name');
    $index=array_search($dimension,$headers,true);
    if($index===false)return [];
    $result=[];
    foreach(array_slice(is_array($report['rows']??null)?$report['rows']:[],0,min($limit,12)) as $row){
        if(!is_array($row))continue;
        $value=$row['dimensionValues'][$index]['value']??null;
        $count=igua_ga4_report_metric($report,$row,$metric);
        if(!is_string($value) || $count===null)continue;
        $label=trim($value);
        if($label==='' || str_starts_with($label,'RESERVED_'))continue;
        $label=preg_replace('/[\x00-\x1F\x7F]/u',' ',$label);
        if(!is_string($label))continue;
        // Evitar registros imensos em sessão e renderização, sem exigir mbstring.
        if(strlen($label)>240)$label=function_exists('mb_strcut')?mb_strcut($label,0,240,'UTF-8'):substr($label,0,240);
        $result[]=['label'=>$label,'count'=>$count];
    }
    return $result;
}
/**
 * Relatório sob demanda. Um OAuth + batchRunReports com quatro consultas na mesma propriedade.
 * Sem persistência no banco, varreduras automáticas, tokens ou respostas brutas em sessão.
 */
function igua_ga4_audience_report(array $credentials,string $property,?callable $transport=null,?int $now=null): array {
    $property=igua_ga4_property_id($property);
    if($property==='')throw new InvalidArgumentException('Informe o ID numérico da propriedade GA4.');
    $transport=$transport??'igua_ga4_google_post';$now=$now??time();
    $token=igua_ga4_access_token($credentials,$transport,$now);
    $dateRanges=[['startDate'=>'28daysAgo','endDate'=>'yesterday']];
    $requests=[
        ['dateRanges'=>$dateRanges,'metrics'=>[['name'=>'activeUsers'],['name'=>'sessions'],['name'=>'screenPageViews']]],
        ['dateRanges'=>$dateRanges,'dimensions'=>[['name'=>'pageTitle']], 'metrics'=>[['name'=>'screenPageViews']], 'limit'=>'10',
            'orderBys'=>[['metric'=>['metricName'=>'screenPageViews'],'desc'=>true]]],
        ['dateRanges'=>$dateRanges,'dimensions'=>[['name'=>'sessionDefaultChannelGroup']], 'metrics'=>[['name'=>'sessions']], 'limit'=>'8',
            'orderBys'=>[['metric'=>['metricName'=>'sessions'],'desc'=>true]]],
        ['dateRanges'=>$dateRanges,'dimensions'=>[['name'=>'deviceCategory']], 'metrics'=>[['name'=>'activeUsers']], 'limit'=>'5',
            'orderBys'=>[['metric'=>['metricName'=>'activeUsers'],'desc'=>true]]],
    ];
    $endpoint='https://analyticsdata.googleapis.com/v1beta/properties/'.$property.':batchRunReports';
    $data=igua_ga4_google_response($transport($endpoint,['Authorization: Bearer '.$token],['requests'=>$requests],false),'relatórios GA4');
    $reports=$data['reports']??null;
    if(!is_array($reports) || count($reports)!==4)throw new RuntimeException('Google não retornou os quatro relatórios esperados.');
    foreach($reports as $report){
        if(!is_array($report) || isset($report['error']))throw new RuntimeException('Resposta incompleta dos relatórios GA4.');
    }
    $summary=null;
    if(isset($reports[0]['rows'][0]) && is_array($reports[0]['rows'][0])){
        $row=$reports[0]['rows'][0];
        $users=igua_ga4_report_metric($reports[0],$row,'activeUsers');
        $sessions=igua_ga4_report_metric($reports[0],$row,'sessions');
        $views=igua_ga4_report_metric($reports[0],$row,'screenPageViews');
        if($users!==null && $sessions!==null && $views!==null)
            $summary=['users'=>$users,'sessions'=>$sessions,'views'=>$views];
    }
    return ['property_id'=>$property,'checked_at'=>$now,'summary'=>$summary,
        'pages'=>igua_ga4_report_rows($reports[1],'pageTitle','screenPageViews',10),
        'channels'=>igua_ga4_report_rows($reports[2],'sessionDefaultChannelGroup','sessions',8),
        'devices'=>igua_ga4_report_rows($reports[3],'deviceCategory','activeUsers',5)];
}
/** Resumo limitado ao ID da propriedade e TTL de uma hora. */
function igua_ga4_audience_last(array $session,string $property,?int $now=null): ?array {
    $report=$session['igua_ga4_audience_last']??null;
    if(!is_array($report) || ($report['property_id']??'')!==$property)return null;
    $now=$now??time();$checked=$report['checked_at']??null;
    if(!is_int($checked) || $checked>$now || ($now-$checked)>3600)return null;
    return $report;
}
