<?php
declare(strict_types=1);
/**
 * Características do PHP que executa a aplicação. Nunca confiar em HTTP_HOST,
 * X-Forwarded-* ou User-Agent para decidir se há privilégios locais.
 * Um site copiado do Termux para a hospedagem é redetectado em cada execução.
 */
function igua_runtime_profile(?array $server=null, ?string $prefix=null, ?string $binary=null, ?string $sapi=null): array {
    $server ??= $_SERVER;
    $prefix ??= (string)(getenv('PREFIX') ?: '');
    $binary ??= PHP_BINARY;
    $sapi ??= PHP_SAPI;
    $termuxPath = static fn(string $value): bool =>
        (bool)preg_match('~^/data/data/com\.termux/files/(?:usr|home)(?:/|$)~D', $value);
    $termux = $termuxPath($prefix) || $termuxPath($binary);
    $kind = $termux ? 'termux' : (in_array($sapi,['cli','cli-server'],true) ? 'local_dev' : 'hosting');
    $secure = isset($server['HTTPS']) && $server['HTTPS'] !== '' && strtolower((string)$server['HTTPS']) !== 'off';
    $secure = $secure || (int)($server['SERVER_PORT']??0) === 443;
    return [
        'kind'=>$kind,
        'label'=>match($kind) {'termux'=>'Termux (Android)', 'hosting'=>'Hospedagem PHP', default=>'PHP local (desenvolvimento)'},
        'db_host'=>$kind==='hosting' ? 'localhost' : '127.0.0.1',
        'db_name'=>$kind==='hosting' ? '' : 'meu_site',
        'https'=>$secure,
        'sapi'=>$sapi,
    ];
}
/**
 * Hospedagens compartilhadas podem usar public_html/ como pasta pública,
 * mantendo app/, storage/ e bin/ como irmãos dela. Só aceitamos uma pasta
 * pública conhecida no MESMO projeto e cujo script realmente está nela.
 */
function igua_public_directory(string $root, ?array $server=null, ?string $hint=null): string {
    $server ??= $_SERVER;
    $hint ??= (string)(getenv('IGUANEWS_PUBLIC_DIR') ?: '');
    $standard=$root.'/public';
    $allowed=['public','public_html','www','htdocs'];
    $safeCliCandidate=static function(string $name) use ($root): ?string {
        $project=realpath($root);
        $candidate=realpath($root.'/'.$name);
        // Aceita symlink apenas quando ele resolve para outra pasta irmã DENTRO do projeto.
        if ($project===false || $candidate===false || dirname($candidate)!==$project
            || !is_file($candidate.'/index.php')) return null;
        return $candidate;
    };
    $document=(string)($server['DOCUMENT_ROOT']??'');
    $script=(string)($server['SCRIPT_FILENAME']??'');
    $resolvedRoot=realpath($root);
    $resolvedDoc=$document!=='' ? realpath($document) : false;
    $resolvedScript=$script!=='' ? realpath($script) : false;
    // Ambiente WEB: somente diretório real que está executando a entrada HTTP.
    if ($resolvedRoot!==false && $resolvedDoc!==false && $resolvedScript!==false
        && in_array(basename($resolvedDoc),$allowed,true)
        && dirname($resolvedDoc)===$resolvedRoot && dirname($resolvedScript)===$resolvedDoc) {
        return $resolvedDoc;
    }
    // Ambiente CLI: cron e tarefas de backup não possuem DocumentRoot.
    // O operador pode selecionar uma pasta pública irmã por nome (nunca caminho arbitrário).
    if ($document==='') {
        if (in_array($hint,$allowed,true) && ($candidate=$safeCliCandidate($hint))) return $candidate;
        if ($candidate=$safeCliCandidate('public')) return $candidate;
        foreach (['public_html','www','htdocs'] as $name) {
            if ($candidate=$safeCliCandidate($name)) return $candidate;
        }
    }
    return $standard;
}

/**
 * Se o DocumentRoot for a pasta do projeto, storage/, app/ e backups ficam
 * expostos. Este layout deve ser bloqueado ANTES de gravar credenciais.
 * A aplicação usa caminhos absolutos /..., portanto requer public/ na raiz.
 */
function igua_webroot_diagnosis(?array $server=null, ?string $public=null): array {
    $server ??= $_SERVER;
    $public ??= IGUA_PUBLIC;
    if (PHP_SAPI === 'cli' && !isset($server['DOCUMENT_ROOT'])) {
        return ['ok'=>true,'detail'=>'Verificação da raiz web será feita pelo PHP servidor.'];
    }
    $documentRoot=(string)($server['DOCUMENT_ROOT']??'');
    $realPublic=realpath($public);
    $realRoot=$documentRoot!=='' ? realpath($documentRoot) : false;
    if ($realPublic!==false && $realRoot!==false && $realRoot===$realPublic) {
        return ['ok'=>true,'detail'=>'Apenas '.basename($realPublic).'/ está acessível pelo navegador.'];
    }
    return ['ok'=>false,'detail'=>'DocumentRoot incompatível ou não verificável. Ele deve apontar para public/ do projeto ou uma pasta pública irmã (public_html/, www/ ou htdocs/) com os arquivos de public/. Nunca para a raiz privada do projeto.'];
}
/** Verifica os arquivos de distribuição presentes na pasta pública de fato servida. */
function igua_public_files_diagnosis(string $public): array {
    $base=realpath($public);
    if ($base===false || !is_dir($base)) {
        return ['ok'=>false,'missing'=>['pasta pública'],'detail'=>'Pasta pública não encontrada.'];
    }
    $needed=['index.php','setup.php','admin.php','router.php','assets/css/style.css','assets/js/news-editor.js'];
    $missing=[];
    foreach ($needed as $name) {
        $file=$public.'/'.$name;
        $real=realpath($file);
        if ($real===false || !is_file($real) || !is_readable($real) || filesize($real)===0
            || !str_starts_with($real,$base.DIRECTORY_SEPARATOR)) {
            $missing[]=$name;
        }
    }
    return [
        'ok'=>$missing===[],
        'missing'=>$missing,
        'detail'=>$missing===[]
            ? 'Entradas PHP, CSS e JavaScript essenciais encontrados na pasta pública.'
            : 'Faltam arquivos na pasta pública: '.implode(', ',$missing).'. Copie TODO o conteúdo de public/ (inclusive assets/) para a raiz web; não copie app/ ou storage/ para ela.',
    ];
}

/** A criação privilegiada de usuários SQL nunca é oferecida em hospedagem. */
function igua_auto_install_allowed(?array $profile=null, ?array $server=null): bool {
    $profile ??= igua_runtime_profile();
    $server ??= $_SERVER;
    return $profile['kind']==='termux'
        && in_array((string)($server['REMOTE_ADDR']??''),['127.0.0.1','::1'],true)
        && in_array((string)($server['SERVER_ADDR']??''),['127.0.0.1','::1',''],true);
}
