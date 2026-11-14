<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/news_editor.php';
require __DIR__.'/../app/backup.php';
function check_editor(bool $ok,string $test): void {if(!$ok)throw new RuntimeException('FALHOU: '.$test);echo "OK: {$test}\n";}
$input=[
 ['kind'=>'fact','heading'=>'Veículos','content'=>'Carro e caminhão','collapsed'=>false],
 ['kind'=>'section','heading'=>'O que aconteceu?','content'=>'Houve colisão.\nMais detalhes dependem de apuração.','collapsed'=>true],
 ['kind'=>'paragraph','heading'=>'','content'=>'Texto adicional.']
];
$blocks=normalize_news_blocks(json_encode($input,JSON_THROW_ON_ERROR));
check_editor(count($blocks)===3&&$blocks[1]['collapsed']===1,'blocos e camadas expansíveis');
check_editor(str_contains(blocks_plain_text($blocks),'Veículos'),'texto compatível para busca e backup');
try{normalize_news_blocks('[{"kind":"script","content":"malicious"}]');throw new RuntimeException('aceitou bloco inseguro');}catch(RuntimeException $e){check_editor(str_contains($e->getMessage(),'inválido'),'tipos de bloco permitidos');}
check_editor(ulen(news_seo_description('Título','',str_repeat('Palavra exemplo ',22)))<=155,'SEO automático limitado');
check_editor(news_seo_description('Título','Resumo curto','Texto longo')==='Resumo curto','prioridade ao resumo confirmado');
$sample=<<<'HTML'
<html><head><title>Título da página</title>
<meta property="og:title" content="Acidente em rodovia estadual" />
<meta name="description" content="Trânsito foi interrompido na região." />
<meta property="article:published_time" content="2026-10-18T08:00:00-03:00" />
<script type="application/ld+json">{"@context":"https://schema.org","@type":"NewsArticle","headline":"Informações verificadas na região","author":{"@type":"Person","name":"Editoria Local"},"datePublished":"2026-10-18T08:00:00-03:00"}</script>
</head><body><h1>Matéria completa não deve ser copiada</h1></body></html>
HTML;
$m=news_meta_from_html($sample,'example.org');
check_editor($m['title']==='Informações verificadas na região'&&$m['author']==='Editoria Local','extrai metadados JSON-LD sem copiar reportagem');
check_editor(!array_key_exists('body',$m),'importador não copia texto integral');
foreach(['http://example.com','https://127.0.0.1/admin','https://localhost/path','https://example.org:8443/','https://user:pwd@example.org/x'] as $bad){
 try{news_safe_url($bad);throw new RuntimeException('URL insegura aceita: '.$bad);}catch(RuntimeException $e){if(str_contains($e->getMessage(),'aceita:'))throw $e;}
}
check_editor(news_safe_url('https://www.example.org/noticia')[1]==='www.example.org','bloqueio de endpoints locais e URLs inseguras');
check_editor(!news_public_ipv4('192.168.1.15')&&!news_public_ipv4('100.80.12.14')&&news_public_ipv4('8.8.8.8'),'bloqueio SSRF redes privadas e CGNAT');
$v6=[];foreach(BACKUP_TABLES_V6 as $table)$v6[$table]=[];
$v6['users']=[['id'=>1,'role'=>'admin','active'=>1,'name'=>'A','email'=>'a@example.test','password_hash'=>'#']];
$d6=parse_backup(json_encode(['format'=>'iguanews-backup','version'=>6,'tables'=>$v6],JSON_THROW_ON_ERROR),'v6.json');
check_editor(array_keys($d6['tables'])===BACKUP_TABLES&&$d6['tables']['article_blocks']===[],'restauração de backup v6');
$v7=array_replace(array_fill_keys(BACKUP_TABLES_V7,[]),$v6);
$v7['article_blocks']=[['id'=>1,'article_id'=>42,'kind'=>'section','heading'=>'Resumo','content'=>'Texto']];
$v7['article_categories']=[['article_id'=>42,'category_id'=>2]];
$v7['article_media']=[['id'=>1,'article_id'=>42,'kind'=>'attachment','filename'=>str_repeat('a',32).'.pdf']];
$d7=parse_backup(json_encode(['format'=>'iguanews-backup','version'=>7,'tables'=>$v7],JSON_THROW_ON_ERROR),'v7.json');
check_editor(count($d7['tables']['article_media'])===1&&count($d7['tables']['article_blocks'])===1,'backup v7 inclui blocos, categorias e anexos');
echo "Todos os testes isolados de editor inteligente aprovados.\n";
