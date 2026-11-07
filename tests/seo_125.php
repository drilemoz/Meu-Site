<?php
declare(strict_types=1);
require_once __DIR__ . '/support/entry_view_source.php';
require_once __DIR__.'/support/news_editor_view_source.php';
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/news_editor.php';
require __DIR__.'/../app/seo_audit.php';
require __DIR__.'/../app/editorial.php';
function audit_ok(bool $v,string $msg):void{if(!$v)throw new RuntimeException($msg);echo "OK: $msg\n";}
$base=[
 'title'=>'Nova obra pública beneficia moradores de Iguatama','seo_title'=>'',
 'summary'=>'A prefeitura anunciou uma obra no centro da cidade, conforme informações divulgadas oficialmente.',
 'seo_description'=>'A prefeitura anunciou uma obra no centro da cidade, conforme informações divulgadas oficialmente.',
 'slug'=>'obra-no-centro-de-iguatama',
 'body'=>str_repeat('Dados confirmados sobre a obra, prazos e locais. ',4),
 'image'=>'2026/10/capa.webp','image_caption'=>'Foto ilustrativa da região central da cidade',
 'cover_alt'=>'Vista do centro da cidade de Iguatama',
];
$a=igua_seo_audit($base);
audit_ok($a['score']===100 && count($a['issues'])===0,'notícia com metadados completos alcança 100 pontos internos');
$b=$base;$b['image']='';$b['image_caption']='';$b['cover_alt']='';
$c=igua_seo_audit($b);
audit_ok($c['score']===80 && count($c['issues'])===2,'ausência de capa também sinaliza ausência de texto alternativo');
$b=$base;$b['slug']='../../etc/passwd';
audit_ok(igua_seo_audit($b)['score']===85,'slug malformado não é pontuado');
$b=$base;$b['seo_title']='X';$b['seo_description']='Curta';$b['summary']='';$b['body']='';
$c=igua_seo_audit($b);audit_ok($c['score']<45 && count($c['issues'])>=4,'campos insuficientes são apontados para revisão');
$b=$base;$b['body']='';$b['has_blocks']=1;
audit_ok(igua_seo_audit($b)['score']===100,'blocos editoriais reais contam como conteúdo');
$b=$base;$b['seo_title']=str_repeat('Í',100);$check=igua_seo_audit($b);
audit_ok(!$check['checks'][0]['ok'],'limite de título considera caracteres UTF-8');
audit_ok(url(['p'=>'noticia','slug'=>'seguranca-mg-050'])==='/index.php?p=noticia&slug=seguranca-mg-050','URL legada compatível quando modo amigável desligado');
audit_ok(url(['p'=>'noticia','slug'=>'../../secreto'])!=='/noticia/../../secreto','URL insegura não assume formato limpo');
audit_ok(safe_seo_url('https://noticias.example/')==='https://noticias.example','endereço canônico HTTPS limpo');
foreach(['http://noticias.example','https://bad:pass@noticias.example','https://noticias.example/#x'] as $bad){
 try {safe_seo_url($bad);throw new LogicException('Aceitou: '.$bad);}catch(InvalidArgumentException){}
}
audit_ok(true,'URLs canônicas inseguras rejeitadas');
$root=dirname(__DIR__);
$source=igua_refactor_page_source('public/index.php');
audit_ok(str_contains($source,"article_slug_redirects")&&str_contains($source,',true,301)'),'rota histórica mantém redirecionamento 301');
audit_ok(str_contains($source,'<link rel="canonical"')&&str_contains($source,'\'noindex,nofollow\''),'portal mantém canonical e proteção contra indexação indevida');
$auditPanel=file_get_contents($root.'/app/seo_admin.php');
audit_ok(str_contains($auditPanel,'a.author_id=?')&&str_contains($auditPanel,'LIMIT 40'),'Central SEO restringe rascunhos de redatores e limita consulta');
audit_ok(!str_contains($auditPanel,'UPDATE articles'),'auditoria não altera conteúdo das notícias');
audit_ok(substr_count(igua_news_editor_view_source(),'data-tab="')===9,'editor agora contém 9 etapas');
echo "Central SEO 1.0.25: testes locais concluídos. Integração com MariaDB permanece pendente.\n";
