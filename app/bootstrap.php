<?php
declare(strict_types=1);

const IGUA_ROOT = __DIR__ . '/..';
const IGUA_STORAGE = IGUA_ROOT . '/storage';
require_once __DIR__.'/environment.php';
require_once __DIR__.'/db_transport.php';
define('IGUA_PUBLIC', igua_public_directory(IGUA_ROOT));
// Atualização incompleta: manter páginas públicas em manutenção, mas permitir
// login e recuperação autenticada pelo atualizador. Nunca expõe diretórios privados.
if (PHP_SAPI!=='cli' && is_file(IGUA_STORAGE.'/.update-maintenance')) {
    $script=basename((string)(parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_PATH)?:''));
    $action=(string)($_GET['action']??'');
    $recover=$script==='admin.php' && in_array($action,['login','logout','updates'],true);
    if(!$recover){http_response_code(503);header('Retry-After: 120');exit('Portal em manutenção para atualização. Tente novamente mais tarde.');}
}

date_default_timezone_set('America/Sao_Paulo');
if (session_status() !== PHP_SESSION_ACTIVE && PHP_SAPI !== 'cli') {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name('iguanews_sid');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function installed(): bool {
    return is_file(IGUA_STORAGE . '/config.php') || getenv('IGUANEWS_DB_NAME') !== false;
}
function config(): array {
    static $config = null;
    if ($config !== null) return $config;
    $saved = is_file(IGUA_STORAGE . '/config.php') ? require IGUA_STORAGE . '/config.php' : [];
    $env = [
        'host' => getenv('IGUANEWS_DB_HOST') ?: null,
        'port' => getenv('IGUANEWS_DB_PORT') ?: null,
        'database' => getenv('IGUANEWS_DB_NAME') ?: null,
        'username' => getenv('IGUANEWS_DB_USER') ?: null,
        'password' => getenv('IGUANEWS_DB_PASSWORD') === false ? null : getenv('IGUANEWS_DB_PASSWORD'),
        'tls' => getenv('IGUANEWS_DB_TLS') === false ? null : getenv('IGUANEWS_DB_TLS')==='1',
    ];
    return $config = array_replace($saved, array_filter($env, static fn($v) => $v !== null));
}
function connection(array $c): PDO {
    $host = $c['host'] ?? '127.0.0.1';
    $port = (int)($c['port'] ?? 3306);
    $name = $c['database'] ?? '';
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/D', $name) || !preg_match('/^[a-zA-Z0-9.\-:_]+$/D', $host) || $port < 1 || $port > 65535) {
        throw new RuntimeException('Configuração do banco inválida.');
    }
    // host and db name intentionally drawn only from setup configuration/env.
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $db = new PDO($dsn, $c['username'] ?? '', $c['password'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 6,
    ] + igua_mysql_tls_options(!empty($c['tls'])));
    $db->exec("SET time_zone = '-03:00'");
    return $db;
}
function db(): PDO {
    static $db = null;
    return $db ??= connection(config());
}
function h(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(array $params = []): string {
    if(count($params)===2 && isset($params['p'],$params['slug']) && in_array($params['p'],['noticia','categoria'],true)
       && is_string($params['slug']) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$params['slug'])
       && setting('seo_pretty_urls','0')==='1') {
        return '/'.$params['p'].'/'.$params['slug'];
    }
    return '/index.php' . ($params ? '?' . http_build_query($params) : '');
}
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.h(csrf_token()).'">'; }
function check_csrf(): void {
    if (!isset($_POST['csrf']) || !hash_equals(csrf_token(), (string)$_POST['csrf'])) {
        if(function_exists('igua_security_event'))igua_security_event('csrf_rejected');
        http_response_code(419); exit('Sessão inválida. Recarregue a página e tente novamente.');
    }
}
function redirect(string $path): never { header('Location: ' . $path, true, 303); exit; }
function setting(string $key, string $default=''): string {
    static $cached=[];
    if (array_key_exists($key,$cached)) return $cached[$key];
    try { $s=db()->prepare('SELECT value FROM settings WHERE name=?'); $s->execute([$key]); return $cached[$key]=(string)($s->fetchColumn() ?: $default); }
    catch (Throwable) { return $default; }
}
function slug(string $text): string {
    $v=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$text);
    $v=strtolower((string)$v);
    $v=preg_replace('/[^a-z0-9]+/', '-', $v) ?: '';
    return trim($v,'-') ?: 'noticia';
}
function asset_image(?string $name): string {
    if (!$name) return '/assets/img/cidade.jpg';
    if (preg_match('/^demo-[a-zA-Z0-9_-]+\.(?:jpg|jpeg|png|webp)$/iD',$name)) {
        $demo=substr($name,5);
        return is_file(IGUA_PUBLIC.'/assets/img/'.$demo) ? '/assets/img/'.rawurlencode($demo) : '/assets/img/cidade.jpg';
    }
    return media_path($name)!==null ? media_url($name) : '/assets/img/cidade.jpg';
}
function readable_date(?string $date): string {
    if (!$date) return '';
    $t=strtotime($date); if (!$t) return '';
    $d=time()-$t;
    if ($d>=0 && $d<3600) return 'há '.max(1,(int)floor($d/60)).' min';
    if ($d>=0 && $d<86400) return 'há '.((int)floor($d/3600)).' horas';
    if ($d>=0 && $d<172800) return 'ontem';
    return date('d/m/Y',$t);
}
function ulen(string $text): int { return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : (function_exists('iconv_strlen') ? iconv_strlen($text, 'UTF-8') : strlen($text)); }
function usub(string $text, int $start, int $length): string { return function_exists('mb_substr') ? mb_substr($text,$start,$length,'UTF-8') : (function_exists('iconv_substr') ? iconv_substr($text,$start,$length,'UTF-8') : substr($text,$start,$length)); }
function excerpt(string $body, int $max=180): string {
    $v=trim(preg_replace('/\s+/', ' ',strip_tags($body)) ?? '');
    return ulen($v)>$max ? usub($v,0,$max-1).'…' : $v;
}
function text_paragraphs(string $text): string {
    $parts=preg_split('/\R\s*\R/u',trim($text)) ?: [];
    return implode('',array_map(static fn($v)=>'<p>'.nl2br(h(trim($v))).'</p>',array_filter($parts)));
}
function published_filter(): string { return "a.status = 'published' AND a.published_at <= NOW()"; }
function admin_user(): ?array { return $_SESSION['admin'] ?? null; }
function require_admin(): void { if (!admin_user()) redirect('/admin.php?action=login'); }
function flash(string $message,string $type='success'): void { $_SESSION['flash']=['message'=>$message,'type'=>$type]; }
function take_flash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
function request_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }
/** Converte limites como 2M, 8M e 512K para bytes, sem arredondamento enganoso. */
function igua_ini_bytes(string $value): int {
    $value=trim($value);
    if(!preg_match('/^(\d+(?:\.\d+)?)([KMG]?)$/iD',$value,$m))return 0;
    $factor=[''=>1,'K'=>1024,'M'=>1048576,'G'=>1073741824][strtoupper($m[2])];
    return (int)min(PHP_INT_MAX,round((float)$m[1]*$factor));
}
/** Explica erros produzidos pelo PHP antes da validação de imagem. */
function igua_upload_error_message(int $code,string $label='Imagem'): string {
    return match($code){
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => $label.' recusada pelo PHP: limite por arquivo configurado em '.(ini_get('upload_max_filesize')?:'valor desconhecido').'. Reduza a foto para JPG de até 1 MB ou ajuste upload_max_filesize e post_max_size no servidor.',
        UPLOAD_ERR_PARTIAL => 'O envio do arquivo foi interrompido. Verifique a conexão e tente novamente.',
        UPLOAD_ERR_NO_FILE => 'Nenhum arquivo recebido. Se selecionou uma foto, verifique post_max_size no PHP.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'Falha no armazenamento temporário do PHP. Verifique espaço livre e permissões do usuário que executa o PHP.',
        UPLOAD_ERR_EXTENSION => 'O PHP bloqueou o arquivo por uma extensão. Verifique a configuração do servidor.',
        default => 'Não foi possível receber a imagem (código '.max(0,$code).'). Tente uma foto JPG menor.'
    };
}
function image_upload(string $field, ?string $previous=null, ?callable $onCreated=null): ?string {
    if (!isset($_FILES[$field])) return $previous;
    $f=$_FILES[$field];
    $error=(int)($f['error']??UPLOAD_ERR_NO_FILE);
    if($error===UPLOAD_ERR_NO_FILE && empty($f['name']))return $previous;
    if($error!==UPLOAD_ERR_OK)throw new RuntimeException(igua_upload_error_message($error));
    if(empty($f['name']))return $previous;
    if((int)$f['size']>4*1024*1024)throw new RuntimeException('Imagem acima de 4 MB. Escolha um JPG menor ou use a otimização automática do editor.');
    if((int)$f['size']<20)throw new RuntimeException('Imagem vazia ou inválida.');
    if (!is_uploaded_file((string)$f['tmp_name'])) throw new RuntimeException('Upload inválido.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
    if (!$ext || @getimagesize($f['tmp_name']) === false) throw new RuntimeException('Envie uma imagem JPG, PNG ou WebP válida.');
    return media_upload(db(),$f,(int)(admin_user()['id']??0)?:null,$onCreated)['filename'];
}

// Logs de exceções fatais apenas com tipo e localização, sem mensagens nem dados de usuário.
require_once __DIR__.'/branding.php';
require_once __DIR__.'/media_library.php';
require_once __DIR__.'/health.php';
require_once __DIR__.'/security_center.php';
igua_security_probe_request();
igua_health_init();
