<?php
declare(strict_types=1);
/**
 * Google AdSense Management API v2. Apenas leitura, OAuth2 do usuário Google.
 * O AdSense NÃO aceita autenticação por conta de serviço. Não utilizar o JSON GA4.
 * Uma identidade publicadora é fixada pelo ca-pub configurado, sem seleção de outro usuário.
 */
const IGUA_ADSENSE_SCOPE = 'https://www.googleapis.com/auth/adsense.readonly';
function igua_adsense_api_path(): string {return IGUA_STORAGE.'/integrations/adsense-oauth.json';}
function igua_adsense_callback_uri(string $site): string {
    $site=safe_seo_url($site);
    $host=(string)(parse_url($site,PHP_URL_HOST)?:'');
    if($site==='' || !preg_match('~^https://[a-z0-9.-]+$~D',$site) || filter_var($host,FILTER_VALIDATE_IP)
        || !str_contains($host,'.') || $host==='localhost')
        throw new InvalidArgumentException('Configure URL pública HTTPS com domínio DNS na raiz em Geral e SEO.');
    return $site.'/admin.php?action=integrations_adsense_callback';
}
function igua_adsense_oauth_client(string $id,string $secret): array {
    $id=trim($id);$secret=trim($secret);
    if(!preg_match('/^[A-Za-z0-9._-]{15,200}\.apps\.googleusercontent\.com$/D',$id)
        || strlen($secret)<6 || strlen($secret)>256 || preg_match('/[\x00-\x1f\x7f]/',$secret))
        throw new InvalidArgumentException('Informe o ID e o segredo do cliente OAuth tipo Aplicação web do Google Cloud.');
    return ['client_id'=>$id,'client_secret'=>$secret];
}
function igua_adsense_api_read(?string $path=null): ?array {
    $path=$path??igua_adsense_api_path();
    if(is_link($path))throw new RuntimeException('Credencial AdSense inválida.');
    if(!is_file($path))return null;
    $size=@filesize($path);
    if($size===false || $size>8192)throw new RuntimeException('Credencial AdSense excede limite.');
    $raw=@file_get_contents($path);
    if(!is_string($raw))throw new RuntimeException('Credencial AdSense inacessível.');
    $data=json_decode($raw,true,8);
    if(!is_array($data))throw new RuntimeException('Credencial AdSense inválida.');
    $client=igua_adsense_oauth_client((string)($data['client_id']??''),(string)($data['client_secret']??''));
    $refresh=$data['refresh_token']??'';
    $account=$data['account']??'';
    if(!is_string($refresh) || strlen($refresh)>4096 || !is_string($account) || strlen($account)>50
        || ($account!=='' && !preg_match('~^accounts/pub-[0-9]{16}$~D',$account)))
        throw new RuntimeException('Credencial AdSense privada inválida.');
    return $client+['refresh_token'=>$refresh,'account'=>$account];
}
function igua_adsense_api_write(array $data,?string $path=null): void {
    $path=$path??igua_adsense_api_path();
    $client=igua_adsense_oauth_client((string)($data['client_id']??''),(string)($data['client_secret']??''));
    $refresh=$data['refresh_token']??'';$account=$data['account']??'';
    if(!is_string($refresh)||strlen($refresh)>4096 || !is_string($account)
        || ($account!==''&&!preg_match('~^accounts/pub-[0-9]{16}$~D',$account)))throw new InvalidArgumentException('Credencial AdSense inválida.');
    $parent=dirname($path);
    if(is_link($parent))throw new RuntimeException('Armazenamento privado inválido.');
    $created=false;
    if(!is_dir($parent)){
        if(!@mkdir($parent,0700,true))throw new RuntimeException('Não foi possível criar diretório privado.');
        $created=true;
    }
    // Nunca alterar permissão de diretórios compartilhados (ex.: /tmp em testes).
    if(($created || str_starts_with($parent,IGUA_STORAGE.'/')) && !@chmod($parent,0700))
        throw new RuntimeException('Não foi possível proteger o diretório privado.');
    if(is_link($path))throw new RuntimeException('Arquivo de credenciais inválido.');
    $tmp=$parent.'/.adsense-'.bin2hex(random_bytes(12)).'.tmp';
    try {
        $handle=@fopen($tmp,'x');if(!$handle)throw new RuntimeException('Falha ao salvar credencial AdSense.');
        @chmod($tmp,0600);
        $json=json_encode($client+['refresh_token'=>$refresh,'account'=>$account],JSON_THROW_ON_ERROR);
        try {if(fwrite($handle,$json)!==strlen($json)||!fflush($handle))throw new RuntimeException('Gravação incompleta.');}
        finally {fclose($handle);}
        if(!@rename($tmp,$path))throw new RuntimeException('Falha ao ativar credencial.');
        @chmod($path,0600);
    }finally{if(is_file($tmp))@unlink($tmp);}
}
function igua_adsense_api_delete(?string $path=null): void {
    $path=$path??igua_adsense_api_path();
    if(is_link($path))throw new RuntimeException('Caminho privado inválido.');
    if(is_file($path)&&!@unlink($path))throw new RuntimeException('Falha ao remover a autorização.');
}
function igua_adsense_api_connected(?array $credentials): bool {
    return is_array($credentials) && ($credentials['refresh_token']??'')!=='' && ($credentials['account']??'')!=='';
}
function igua_adsense_publisher_account(string $publisher): string {
    if(!preg_match('/^ca-pub-([0-9]{16})$/D',$publisher,$match))throw new InvalidArgumentException('Configure primeiro o identificador ca-pub do AdSense.');
    return 'accounts/pub-'.$match[1];
}
function igua_adsense_api_authorize(array $client,string $callback,array &$session,int $userId,?int $now=null): string {
    $client=igua_adsense_oauth_client($client['client_id'],$client['client_secret']);
    if($userId<1)throw new InvalidArgumentException('Administrador inválido.');
    $state=bin2hex(random_bytes(32));$verifier=rtrim(strtr(base64_encode(random_bytes(32)),'+/','-_'),'=');
    $challenge=rtrim(strtr(base64_encode(hash('sha256',$verifier,true)),'+/','-_'),'=');
    $session['igua_adsense_oauth_pending']=['state'=>$state,'verifier'=>$verifier,'user_id'=>$userId,'time'=>$now??time(),'callback'=>$callback];
    $params=['client_id'=>$client['client_id'],'redirect_uri'=>$callback,'response_type'=>'code',
        'scope'=>IGUA_ADSENSE_SCOPE,'access_type'=>'offline','prompt'=>'consent',
        'state'=>$state,'code_challenge'=>$challenge,'code_challenge_method'=>'S256'];
    return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params,'','&',PHP_QUERY_RFC3986);
}
function igua_adsense_api_oauth_pending(array &$session,mixed $state,int $userId,string $callback,?int $now=null): string {
    $pending=$session['igua_adsense_oauth_pending']??null;
    unset($session['igua_adsense_oauth_pending']); // Consumir antes de rede para impedir replay.
    $now=$now??time();
    if(!is_array($pending) || !is_string($state) || strlen($state)!==64
        || !is_string($pending['state']??null) || !hash_equals($pending['state'],$state)
        || ($pending['user_id']??null)!==$userId || ($pending['callback']??null)!==$callback
        || !is_int($pending['time']??null) || $pending['time']>$now || $now-$pending['time']>600
        || !is_string($pending['verifier']??null) || !preg_match('/^[A-Za-z0-9_-]{43,128}$/D',$pending['verifier']))
        throw new RuntimeException('Autorização Google expirada ou inválida. Repita a conexão.');
    return $pending['verifier'];
}
/** Transporte restrito a dois hosts oficiais, sem redirects, TLS verificado e corpo limitado. */
function igua_adsense_api_http(string $url,array $headers=[],?array $form=null): array {
    $allowed=$url==='https://oauth2.googleapis.com/token'
        || $url==='https://adsense.googleapis.com/v2/accounts'
        || preg_match('~^https://adsense\.googleapis\.com/v2/accounts/pub-[0-9]{16}/reports:generate\?[A-Za-z0-9=&._~%-]+$~D',$url);
    if(!$allowed)throw new RuntimeException('Endpoint Google não permitido.');
    if(!function_exists('curl_init'))throw new RuntimeException('A hospedagem precisa da extensão PHP cURL.');
    $ch=curl_init($url);if(!$ch)throw new RuntimeException('Não foi possível iniciar conexão segura.');
    $body='';$overflow=false;
    $options=[CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>18,
        CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_RETURNTRANSFER=>false,
        CURLOPT_HTTPHEADER=>array_merge(['Accept: application/json'],$headers),
        CURLOPT_WRITEFUNCTION=>static function($c,string $part)use(&$body,&$overflow):int {
            if(strlen($body)+strlen($part)>131072){$overflow=true;return 0;}
            $body.=$part;return strlen($part);
        }];
    if($form!==null){
        if($url!=='https://oauth2.googleapis.com/token')throw new RuntimeException('POST externo proibido.');
        $options[CURLOPT_POST]=true;
        $options[CURLOPT_POSTFIELDS]=http_build_query($form,'','&',PHP_QUERY_RFC3986);
        $options[CURLOPT_HTTPHEADER]=array_merge($options[CURLOPT_HTTPHEADER],['Content-Type: application/x-www-form-urlencoded']);
    }
    curl_setopt_array($ch,$options);$ok=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
    if($overflow)throw new RuntimeException('Resposta grande demais do Google.');
    if($ok===false)throw new RuntimeException('Falha de rede TLS na API AdSense.');
    return ['status'=>$status,'body'=>$body];
}
function igua_adsense_api_response(array $response,string $stage): array {
    $status=(int)($response['status']??0);
    if($status===401)throw new RuntimeException('Autorização Google expirada ou revogada. Reconecte o AdSense.');
    if($status===403)throw new RuntimeException('Sem permissão no AdSense (403). Confira a conta Google e a API habilitada.');
    if($status===429)throw new RuntimeException('Limite de consultas do AdSense atingido. Tente mais tarde.');
    if($status<200 || $status>=300)throw new RuntimeException('Google recusou a operação '.$stage.' (HTTP '.$status.').');
    $data=json_decode((string)($response['body']??''),true,16);
    if(!is_array($data))throw new RuntimeException('Resposta inválida do Google na etapa '.$stage.'.');
    return $data;
}
function igua_adsense_api_token(array $data,string $key): string {
    $value=$data[$key]??null;
    if(!is_string($value)||strlen($value)<10||strlen($value)>4096||preg_match('/[\x00-\x20\x7f]/',$value))
        throw new RuntimeException('Google não forneceu autorização válida.');
    return $value;
}
/** Finaliza o OAuth depois de validar state/PKCE no controlador. */
function igua_adsense_api_exchange(array $client,string $code,string $callback,string $verifier,?callable $transport=null): array {
    if(strlen($code)<8||strlen($code)>2048||preg_match('/[\x00-\x20\x7f]/',$code))throw new InvalidArgumentException('Código Google inválido.');
    $transport=$transport??'igua_adsense_api_http';
    $data=igua_adsense_api_response($transport('https://oauth2.googleapis.com/token',[],[
        'client_id'=>$client['client_id'],'client_secret'=>$client['client_secret'],'code'=>$code,
        'code_verifier'=>$verifier,'redirect_uri'=>$callback,'grant_type'=>'authorization_code']),'autorização');
    return ['access_token'=>igua_adsense_api_token($data,'access_token'), 'refresh_token'=>igua_adsense_api_token($data,'refresh_token')];
}
function igua_adsense_api_access(array $credentials,?callable $transport=null): string {
    if(!igua_adsense_api_connected($credentials))throw new RuntimeException('Conecte sua conta AdSense primeiro.');
    $transport=$transport??'igua_adsense_api_http';
    $data=igua_adsense_api_response($transport('https://oauth2.googleapis.com/token',[],[
        'client_id'=>$credentials['client_id'],'client_secret'=>$credentials['client_secret'],
        'refresh_token'=>$credentials['refresh_token'],'grant_type'=>'refresh_token']),'renovação de token');
    return igua_adsense_api_token($data,'access_token');
}
/** Compara com as contas retornadas, não confia no ID digitado como prova de acesso. */
function igua_adsense_api_account(string $accessToken,string $publisher,?callable $transport=null): string {
    $wanted=igua_adsense_publisher_account($publisher);
    $transport=$transport??'igua_adsense_api_http';
    $data=igua_adsense_api_response($transport('https://adsense.googleapis.com/v2/accounts',['Authorization: Bearer '.$accessToken]),'lista de contas');
    $accounts=$data['accounts']??[];
    if(!is_array($accounts))throw new RuntimeException('Resposta de contas AdSense inválida.');
    foreach($accounts as $item){
        if(is_array($item) && ($item['name']??null)===$wanted)return $wanted;
    }
    throw new RuntimeException('A conta Google autorizada não contém o ID ca-pub cadastrado no portal.');
}
function igua_adsense_publisher_account_safe(string $publisher): string {
    try{return igua_adsense_publisher_account($publisher);}catch(Throwable){return '';}
}
function igua_adsense_api_report_parse(array $report,string $account,int $now): array {
    $headers=$report['headers']??null;
    if(!is_array($headers))throw new RuntimeException('AdSense não retornou as colunas do relatório.');
    $names=array_column($headers,'name');
    $metrics=['ESTIMATED_EARNINGS','IMPRESSIONS','CLICKS','PAGE_VIEWS'];
    $values=[];$currency='';
    $rawRow=$report['totals']['cells']??null;
    if(!is_array($rawRow)){
        $rawRow=$report['rows'][0]['cells']??null; // Sem dimensões, uma linha de agregados.
    }
    if(!is_array($rawRow))return ['account'=>$account,'checked_at'=>$now,'currency'=>'','totals'=>null];
    foreach($metrics as $name){
        $index=array_search($name,$names,true);
        if($index===false||!is_array($headers[$index]??null))throw new RuntimeException('AdSense não retornou a métrica '.$name.'.');
        $v=$rawRow[$index]['value']??null;
        if(!is_string($v)||!preg_match('/^(?:0|[1-9][0-9]{0,11})(?:\.[0-9]{1,9})?$/D',$v))
            throw new RuntimeException('Métrica AdSense incompleta ou inválida.');
        if($name!=='ESTIMATED_EARNINGS' && !preg_match('/^[0-9]{1,12}$/D',$v))throw new RuntimeException('Contagem inválida no AdSense.');
        $values[$name]=$v;
        if($name==='ESTIMATED_EARNINGS'){
            $currency=(string)($headers[$index]['currencyCode']??'');
            if(!preg_match('/^[A-Z]{3}$/D',$currency))$currency='';
        }
    }
    return ['account'=>$account,'checked_at'=>$now,'currency'=>$currency,'totals'=>[
        'earnings'=>$values['ESTIMATED_EARNINGS'],'impressions'=>(int)$values['IMPRESSIONS'],
        'clicks'=>(int)$values['CLICKS'],'page_views'=>(int)$values['PAGE_VIEWS']]];
}
function igua_adsense_api_report(array $credentials,string $publisher,?callable $transport=null,?int $now=null): array {
    $transport=$transport??'igua_adsense_api_http';$now=$now??time();
    $account=igua_adsense_publisher_account($publisher);
    if(($credentials['account']??null)!==$account)throw new RuntimeException('O ID do editor foi alterado. Reconecte a conta AdSense.');
    $token=igua_adsense_api_access($credentials,$transport);
    // Consulta novamente a conta autorizada e só então lê o relatório.
    igua_adsense_api_account($token,$publisher,$transport);
    // A API REST v2 espera parâmetros repetidos `metrics=...`, não `metrics[0]=...`.
    $params='dateRange=LAST_30_DAYS&metrics=ESTIMATED_EARNINGS&metrics=IMPRESSIONS'.
        '&metrics=CLICKS&metrics=PAGE_VIEWS&languageCode=pt-BR&limit=1';
    $url='https://adsense.googleapis.com/v2/'.$account.'/reports:generate?'.$params;
    $data=igua_adsense_api_response($transport($url,['Authorization: Bearer '.$token]),'relatório');
    return igua_adsense_api_report_parse($data,$account,$now);
}
function igua_adsense_api_last(array $session,string $publisher,?int $now=null): ?array {
    $result=$session['igua_adsense_api_last']??null;
    if(!is_array($result))return null;
    try{$account=igua_adsense_publisher_account($publisher);}catch(Throwable){return null;}
    $checked=$result['checked_at']??null;$now=$now??time();
    if(($result['account']??'')!==$account||!is_int($checked)||$checked>$now||$now-$checked>3600)return null;
    return $result;
}
