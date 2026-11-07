<?php
/** Roteador para o servidor embutido do PHP. Não substitui o index.php existente. */
declare(strict_types=1);
$path=rawurldecode((string)(parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)??'/'));
// Bloqueia arquivos ocultos (.user.ini, .htaccess, .env) no servidor embutido.
// A exceção .well-known permite desafios ACME em servidores configurados para isso.
if(preg_match('~(?:^|/)\.(?!well-known(?:/|$))~D',$path)){
    http_response_code(404);header('Content-Type: text/plain; charset=UTF-8');echo 'Página não encontrada.';return true;
}
// Não permitir que nomes relativos/duplamente codificados sejam servidos fora de public/.
if(!str_starts_with($path,'/') || str_contains($path,'\\') || str_contains($path,chr(0))
   || str_contains($path,'%') || preg_match('~(?:^|/)\.{1,2}(?:/|$)~',$path)){
    http_response_code(404);header('Content-Type: text/plain; charset=UTF-8');echo 'Página não encontrada.';return true;
}
if(preg_match('~^/(noticia|categoria)/([a-z0-9]+(?:-[a-z0-9]+)*)/?$~D',$path,$m)){
    $_GET['p']=$m[1];$_GET['slug']=$m[2];
    require __DIR__.'/index.php';return true;
}
if(preg_match('~^/([a-z0-9]+(?:-[a-z0-9]+)*)(?:/([a-z0-9]+(?:-[a-z0-9]+)*))?/?$~D',$path,$m)){
    $_GET['p']='app';$_GET['app_slug']=$m[1];$_GET['app_page']=$m[2]??'inicio';
    require __DIR__.'/index.php';return true;
}
// Arquivos existentes e raiz seguem atendidos pelo servidor embutido.
if($path==='/' || is_file(__DIR__.$path))return false;
// Rotas desconhecidas não devem virar página inicial com status 200 (SEO).
http_response_code(404);
header('Content-Type: text/plain; charset=UTF-8');
echo 'Página não encontrada.';
return true;
