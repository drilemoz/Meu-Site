<?php
declare(strict_types=1);
/** Inspeção editorial local, sem chamadas HTTP nem URLs informadas livremente. */
require_once __DIR__.'/seo_audit.php';

function igua_page_inspect_selector(mixed $input): string {
    if ($input === null || $input === '') return 'home';
    if (!is_string($input) || strlen($input) > 32) return 'home';
    if ($input === 'home') return 'home';
    if (!preg_match('/^(article|category):([1-9][0-9]{0,8})$/D', $input)) return 'home';
    return $input;
}
function igua_page_inspect_options(PDO $db): array {
    $items = [['value'=>'home','label'=>'Página inicial']];
    $articles = $db->query("SELECT id,title FROM articles WHERE status='published' AND published_at<=NOW() ORDER BY published_at DESC,id DESC LIMIT 35")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($articles as $article) {
        $items[] = ['value'=>'article:'.(int)$article['id'], 'label'=>'Notícia · '.(string)$article['title']];
    }
    $cats = $db->query('SELECT id,name FROM categories ORDER BY sort_order,name LIMIT 20')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cats as $cat) {
        $items[] = ['value'=>'category:'.(int)$cat['id'], 'label'=>'Categoria · '.(string)$cat['name']];
    }
    return $items;
}
function igua_page_inspect_subject(PDO $db, string $selector): array {
    if ($selector === 'home') return ['kind'=>'home','title'=>'Página inicial'];
    if (preg_match('/^article:([1-9][0-9]{0,8})$/D',$selector,$m)) {
        $stmt=$db->prepare("SELECT a.id,a.title,a.seo_title,a.seo_description,a.summary,LEFT(a.body,3000) AS body,a.slug,a.image,a.image_caption,a.is_demo,a.status,a.published_at,a.updated_at,COALESCE(ml.alt_text,'') AS cover_alt, EXISTS(SELECT 1 FROM article_blocks b WHERE b.article_id=a.id) AS has_blocks FROM articles a LEFT JOIN media_library ml ON ml.filename=a.image AND ml.deleted_at IS NULL WHERE a.id=? AND a.status='published' AND a.published_at<=NOW() LIMIT 1");
        $stmt->execute([(int)$m[1]]);
        $row=$stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return ['kind'=>'article','title'=>(string)$row['title'],'data'=>$row];
    }
    if (preg_match('/^category:([1-9][0-9]{0,8})$/D',$selector,$m)) {
        $stmt=$db->prepare('SELECT id,name,slug FROM categories WHERE id=? LIMIT 1');
        $stmt->execute([(int)$m[1]]);
        $row=$stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return ['kind'=>'category','title'=>(string)$row['name'],'data'=>$row];
    }
    return ['kind'=>'missing','title'=>'Página não encontrada'];
}
function igua_page_inspect_relative(array $subject, bool $pretty): string {
    $kind=$subject['kind']??'missing';
    if ($kind==='home') return '/index.php';
    if ($kind!=='article' && $kind!=='category') return '';
    $slug=(string)($subject['data']['slug']??'');
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)) return '';
    $part=$kind==='article'?'noticia':'categoria';
    return $pretty?'/'.$part.'/'.$slug:'/index.php?p='.$part.'&slug='.rawurlencode($slug);
}
/**
 * Devolve verificações de preparação a partir do banco e dos arquivos locais.
 * NÃO mede resposta HTTP, disponibilidade externa, indexação no Google ou aprovação.
 */
function igua_page_inspect_report(array $subject,array $cfg,string $publicRoot): array {
    $checks=[];
    $add=static function(string $label,string $status,string $detail) use (&$checks): void {
        $checks[]=['label'=>$label,'status'=>$status,'detail'=>$detail];
    };
    $kind=$subject['kind']??'missing';
    $base='';
    try {$base=safe_seo_url((string)($cfg['seo_site_url']??''));}catch(Throwable) {}
    $pretty=($cfg['seo_pretty_urls']??'0')==='1';
    $relative=igua_page_inspect_relative($subject,$pretty);
    $canonical=($base!=='' && $relative!=='')?$base.$relative:'';
    $add('URL canônica configurada',$canonical!==''?'ok':'attention',$canonical!==''?'Prévia: '.$canonical:'Configure a URL HTTPS em Geral e SEO.');
    if ($kind==='missing') {
        $add('Página selecionada','error','A página não existe ou a notícia não está publicada.');
        return ['checks'=>$checks,'canonical'=>'','kind'=>$kind,'counts'=>igua_page_inspect_counts($checks)];
    }
    $indexEnabled=($cfg['seo_index']??'0')==='1';
    $isDemo=$kind==='article' && (int)($subject['data']['is_demo']??0)===1;
    $hasDemo=($cfg['has_demo']??false)===true;
    $indexable=$indexEnabled && !$isDemo && ($kind==='article'||!$hasDemo);
    $add('Permissão de indexação',$indexable?'ok':'attention',$isDemo?'Notícia de demonstração: noindex intencional.':(!$indexEnabled?'Indexação global desativada.':($hasDemo?'O portal contém demonstrações; páginas gerais ficam noindex.':'O robots desta página permite indexação.')));
    if ($kind==='article') {
        $a=$subject['data'];
        $headline=trim((string)(($a['seo_title']??'') ?: ($a['title']??'')));
        $description=trim((string)(($a['seo_description']??'') ?: ($a['summary']??'')));
        $len=ulen($headline);
        $add('Título editorial',$len>=20 && $len<=90?'ok':'attention','Título informado: '.$len.' caracteres; referência de revisão entre 20 e 90.');
        $descLen=ulen($description);
        $add('Descrição de pesquisa',$descLen>=50 && $descLen<=160?'ok':'attention','Descrição/resumo: '.$descLen.' caracteres; referência entre 50 e 160.');
        $image=(string)($a['image']??'');
        if ($image==='') {
            $add('Imagem de capa','attention','Nenhuma imagem cadastrada; o portal usa imagem genérica.');
        } else {
            $isDemoImage=(bool)preg_match('/^demo-[a-zA-Z0-9_-]+\.(?:jpg|jpeg|png|webp)$/iD',$image);
            $present=$isDemoImage
                ? is_file(rtrim($publicRoot,'/').'/assets/img/'.substr($image,5))
                : media_path($image)!==null;
            $add('Arquivo da imagem',$present?'ok':'error',$present?'Arquivo encontrado no armazenamento público do portal.':'Imagem cadastrada, mas não encontrada no armazenamento local; o portal usa fallback.');
        }
        $alt=trim((string)(($a['cover_alt']??'') ?: ($a['image_caption']??'')));
        $add('Texto alternativo',$alt!=='' && ulen($alt)>=10?'ok':'attention','Confira descrição acessível, legenda e crédito da imagem.');
        $add('Dados estruturados NewsArticle',$indexable && $canonical!==''?'ok':'attention',$indexable && $canonical!==''?'O template está preparado para emitir NewsArticle com URL canônica. Não foi validado em um rastreador externo.':'O template só emite NewsArticle quando a notícia é indexável e tem URL HTTPS canônica.');
        $seo=igua_seo_audit($a);
        $add('Checklist editorial',($seo['score']??0)>=80?'ok':'attention','Pontuação local: '.(int)($seo['score']??0).'/100. Não representa nota ou aprovação Google.');
    }elseif($kind==='category') {
        $add('Endereço da categoria',$relative!==''?'ok':'attention',$relative!==''?'Slug válido para navegação local.':'Slug da categoria precisa ser revisado.');
        $add('Metadados de compartilhamento','ok','Template público inclui título, descrição e Open Graph; não foi inspecionado remotamente.');
    }else {
        $add('Metadados da página inicial','ok','Template público inclui título, descrição e Open Graph; não foi inspecionado remotamente.');
    }
    $add('Rota pública do portal',is_file(rtrim($publicRoot,'/').'/index.php')?'ok':'error','Presença local do index.php; não substitui teste HTTP na hospedagem.');
    $add('Sitemap local',is_file(rtrim($publicRoot,'/').'/sitemap.php')?'ok':'attention','Arquivo sitemap.php presente ou ausente; não comprova que a URL foi enviada ou aceita.');
    $add('Status no índice do Google','unknown','Não consultado: a API de inspeção exige OAuth autorizado e propriedade verificada no Search Console.');
    return ['checks'=>$checks,'canonical'=>$canonical,'kind'=>$kind,'counts'=>igua_page_inspect_counts($checks)];
}
function igua_page_inspect_counts(array $checks): array {
    $counts=['ok'=>0,'attention'=>0,'error'=>0,'unknown'=>0];
    foreach($checks as $check){$key=$check['status']??'unknown';if(isset($counts[$key]))$counts[$key]++;}
    return $counts;
}
