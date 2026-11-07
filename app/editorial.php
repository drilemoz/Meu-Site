<?php
declare(strict_types=1);
function role_label(string $role): string {return ['admin'=>'Administrador','editor'=>'Editor','author'=>'Redator'][$role]??'Sem acesso';}
function role_allowed(string $role,string $ability): bool {
    $permissions=[
      'admin'=>['read','write','publish','edit_all','manage_taxonomy','manage_bus','manage_users','manage_settings','backup','delete'],
      'editor'=>['read','write','publish','edit_all','manage_taxonomy','manage_bus','delete'],
      'author'=>['read','write']
    ];
    return in_array($ability,$permissions[$role]??[],true);
}
function can_edit_article(array $user,array $article): bool {
    return role_allowed((string)$user['role'],'edit_all') || ((string)$user['role']==='author' && (int)$article['author_id']===(int)$user['id'] && $article['status']==='draft');
}
function require_ability(string $ability,array $user): void {
    if (!role_allowed((string)$user['role'],$ability)) {if(function_exists('igua_security_event'))igua_security_event('permission_denied');http_response_code(403);exit('Acesso negado. Sua função não permite esta operação.');}
}
function safe_seo_url(string $url): string {
    $url=trim($url);
    if ($url==='')return '';
    if (strlen($url)>240 || !filter_var($url,FILTER_VALIDATE_URL))throw new InvalidArgumentException('Informe uma URL completa e válida, iniciada com https://.');
    $parts=parse_url($url);
    if (($parts['scheme']??'')!=='https' || !isset($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment']) || isset($parts['query']))throw new InvalidArgumentException('Use HTTPS sem usuário, senha ou fragmentos na URL.');
    return rtrim($url,'/');
}
function clean_seo_text(string $value,int $max): string {
    $value=trim(preg_replace('/\s+/u',' ',$value)??'');
    if(ulen($value)>$max)throw new InvalidArgumentException('Campo SEO excedeu o limite de '.$max.' caracteres.');
    return $value;
}
function page_title(string $headline): string {
    $template=setting('seo_title_template','%title% | %site%');
    // Ajuste da configuração legada sem alterar manualmente as escolhas de SEO.
    if ($template==='%title% | IguaNews') $template='%title% | %site%';
    return str_replace(['%title%','%site%'],[$headline,igua_brand_name()],$template);
}
