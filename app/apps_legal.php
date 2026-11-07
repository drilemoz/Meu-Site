<?php
declare(strict_types=1);
/** Aplicativos e páginas legais: dados editáveis, snapshots publicados e URLs permanentes. */

function igua_app_slug_valid(string $slug): bool {
    return strlen($slug) >= 2 && strlen($slug) <= 90
        && (bool)preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug);
}
function igua_app_slug_reserved(string $slug): bool {
    static $reserved = ['admin','admin-php','index','index-php','setup','setup-php','assets','uploads',
        'api','api-cities','download','sitemap','robots','ads','ads-txt','favicon','favicon-ico',
        'noticia','categoria','inicio','buscar','sobre','privacidade','termos','contato','anuncie',
        'onibus','mais','login','logout','apps','aplicativos','app','www','static','.well-known'];
    if (in_array($slug, $reserved, true)) return true;
    if (defined('IGUA_PUBLIC') && (file_exists(IGUA_PUBLIC.'/'.$slug) || file_exists(IGUA_PUBLIC.'/'.$slug.'.php'))) return true;
    return false;
}
function igua_app_assert_slug(PDO $db, string $slug, int $ignoreId=0): void {
    if(!igua_app_slug_valid($slug) || igua_app_slug_reserved($slug))
        throw new InvalidArgumentException('Identificador inválido ou reservado. Use 2 a 90 letras minúsculas, números e hífens.');
    $q=$db->prepare('SELECT id FROM legal_apps WHERE slug=? LIMIT 1');$q->execute([$slug]);
    $owner=(int)($q->fetchColumn() ?: 0);
    if($owner!==0 && $owner!==$ignoreId) throw new InvalidArgumentException('Esse endereço já pertence a outro aplicativo.');
    $q=$db->prepare('SELECT app_id FROM legal_app_slug_redirects WHERE old_slug=? LIMIT 1');$q->execute([$slug]);
    $alias=(int)($q->fetchColumn() ?: 0);
    if($alias!==0 && $alias!==$ignoreId)throw new InvalidArgumentException('Esse endereço está reservado para uma URL antiga.');
}
function igua_app_page_slug(string $kind, string $slug=''): string {
    return match($kind){'home'=>'inicio','privacy'=>'privacidade','terms'=>'termos',default=>$slug};
}
function igua_app_page_url(string $appSlug, string $pageSlug='inicio'): string {
    return '/'.$appSlug.($pageSlug==='inicio'?'':'/'.$pageSlug);
}
/** Texto formatado armazenado como Markdown restrito, nunca HTML arbitrário. */
function igua_app_inline_markdown(string $value): string {
    $parts=preg_split('~(\[[^\[\]\r\n]{1,160}\]\((?:https://[^\s()<>"]+|mailto:[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+|/[^\s()<>"]+)\))~u',$value,-1,PREG_SPLIT_DELIM_CAPTURE)?:[$value];
    $out='';
    foreach($parts as $part){
        if(preg_match('~^\[([^\[\]\r\n]+)\]\(([^\s()<>"]+)\)$~u',$part,$m)){
            $url=$m[2];
            $valid = (str_starts_with($url,'https://') && filter_var($url,FILTER_VALIDATE_URL))
                || (str_starts_with($url,'mailto:') && filter_var(substr($url,7),FILTER_VALIDATE_EMAIL))
                || (str_starts_with($url,'/') && !str_starts_with($url,'//') && !str_contains($url,'\\') && !str_contains($url,'%'));
            if($valid){$out.='<a href="'.h($url).'" rel="noopener noreferrer">'.h($m[1]).'</a>';continue;}
        }
        $safe=h($part);
        // Formatação apenas após escapar a entrada.
        $safe=preg_replace('/\*\*([^*\r\n]{1,400})\*\*/u','<strong>$1</strong>',$safe)??$safe;
        $safe=preg_replace('/(?<!\*)\*([^*\r\n]{1,400})\*(?!\*)/u','<em>$1</em>',$safe)??$safe;
        $out.=$safe;
    }
    return $out;
}
function igua_app_render_markdown(string $source): string {
    $source=str_replace(["\r\n","\r"],"\n",$source);
    $lines=explode("\n",$source);
    $out='';$list='';$paragraph=[];
    $flushParagraph=static function()use(&$out,&$paragraph):void{
        if($paragraph){$out.='<p>'.igua_app_inline_markdown(implode(' ',$paragraph)).'</p>'; $paragraph=[];}
    };
    $closeList=static function()use(&$out,&$list):void{if($list!==''){$out.='</'.$list.'>';$list='';}};
    foreach($lines as $line){
        $line=trim($line);
        if($line===''){$flushParagraph();$closeList();continue;}
        if(preg_match('/^(#{1,3})\s+(.+)$/u',$line,$m)){
            $flushParagraph();$closeList();$level=strlen($m[1])+1;
            $out.='<h'.$level.'>'.igua_app_inline_markdown($m[2]).'</h'.$level.'>';continue;
        }
        $type='';$content='';
        if(preg_match('/^[-*]\s+(.+)$/u',$line,$m)){$type='ul';$content=$m[1];}
        elseif(preg_match('/^\d{1,3}\.\s+(.+)$/u',$line,$m)){$type='ol';$content=$m[1];}
        if($type!==''){$flushParagraph();if($list!==$type){$closeList();$out.='<'.$type.'>';$list=$type;}$out.='<li>'.igua_app_inline_markdown($content).'</li>';continue;}
        $closeList();$paragraph[]=$line;
    }
    $flushParagraph();$closeList();return $out;
}
function igua_app_clean(string $value,int $max,string $label,bool $required=false): string {
    $value=trim($value);
    if($required && $value==='')throw new InvalidArgumentException('Informe '.$label.'.');
    if(ulen($value)>$max)throw new InvalidArgumentException($label.' excede '.$max.' caracteres.');
    return $value;
}
function igua_app_external_url(string $url): string {
    $url=trim($url);
    if($url==='')return '';
    if(strlen($url)>600 || !filter_var($url,FILTER_VALIDATE_URL) || !str_starts_with(strtolower($url),'https://'))
        throw new InvalidArgumentException('O link externo deve usar HTTPS e ter até 600 caracteres.');
    $parts=parse_url($url);
    if(isset($parts['user']) || isset($parts['pass']))throw new InvalidArgumentException('Link externo inválido.');
    return $url;
}
function igua_app_legal_template(string $kind,string $name): string {
    if($kind==='home') return '# '. $name ."\n\nPREENCHER: descreva o aplicativo e suas funções reais.\n\n- PREENCHER: recursos principais.\n- PREENCHER: como obter suporte.";
    if($kind==='privacy')return "# Política de Privacidade\n\nPREENCHER: identifique o desenvolvedor e o aplicativo.\n\n## Dados coletados\nPREENCHER: quais dados realmente são coletados ou acessados.\n\n## Finalidades e serviços externos\nPREENCHER: descreva uso, inclusive integrações com Google/YouTube, se existirem.\n\n## Armazenamento e retenção\nPREENCHER: descreva locais, segurança e prazos efetivos.\n\n## Autorização, revogação e exclusão\nPREENCHER: descreva como revogar autorizações Google/YouTube, excluir dados e contatar o responsável.\n\n## Contato e atualização\nPREENCHER: contato e data de atualização.";
    if($kind==='terms')return "# Termos de Serviço\n\nPREENCHER: identifique o aplicativo e o responsável.\n\n## Uso e limitações\nPREENCHER: defina regras reais e limitações.\n\n## Integrações externas\nPREENCHER: descreva serviços de terceiros realmente utilizados.\n\n## Contato e alterações\nPREENCHER: endereço de contato e atualização.";
    return '';
}
function igua_app_seed_default_pages(PDO $db,int $appId,string $name):void {
    $query=$db->prepare("INSERT INTO legal_app_pages (app_id,kind,slug,draft_title,draft_body,draft_seo_description) VALUES (?,?,?,?,?,?)");
    foreach([['home','inicio','Sobre '.$name],['privacy','privacidade','Política de Privacidade'],['terms','termos','Termos de Serviço']] as [$kind,$slug,$title]){
        $query->execute([$appId,$kind,$slug,$title,igua_app_legal_template($kind,$name),'']);
    }
}
function igua_migrate_apps_schema(PDO $db):void {
    $db->exec("CREATE TABLE IF NOT EXISTS legal_apps (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(160) NOT NULL, slug VARCHAR(90) NOT NULL UNIQUE,
      summary VARCHAR(300) NOT NULL DEFAULT '', description MEDIUMTEXT NOT NULL,
      logo_filename VARCHAR(150) NOT NULL DEFAULT '', developer_name VARCHAR(180) NOT NULL DEFAULT '',
      support_email VARCHAR(190) NOT NULL DEFAULT '', website_url VARCHAR(600) NOT NULL DEFAULT '',
      status ENUM('draft','published') NOT NULL DEFAULT 'draft',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      published_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS legal_app_pages (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      app_id INT UNSIGNED NOT NULL, kind ENUM('home','privacy','terms','custom') NOT NULL,
      slug VARCHAR(90) NOT NULL,
      draft_title VARCHAR(180) NOT NULL DEFAULT '', draft_body MEDIUMTEXT NOT NULL,
      draft_seo_description VARCHAR(240) NOT NULL DEFAULT '',
      status ENUM('draft','published') NOT NULL DEFAULT 'draft',
      published_revision_id INT UNSIGNED NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uq_legal_page_slug (app_id,slug), INDEX idx_legal_page_public (app_id,status),
      CONSTRAINT fk_legal_page_app FOREIGN KEY (app_id) REFERENCES legal_apps(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS legal_app_revisions (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      page_id INT UNSIGNED NOT NULL, revision_number INT UNSIGNED NOT NULL,
      title VARCHAR(180) NOT NULL, body MEDIUMTEXT NOT NULL,
      seo_description VARCHAR(240) NOT NULL DEFAULT '',
      created_by INT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_legal_page_revision (page_id,revision_number),
      CONSTRAINT fk_legal_revision_page FOREIGN KEY (page_id) REFERENCES legal_app_pages(id) ON DELETE CASCADE,
      CONSTRAINT fk_legal_revision_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS legal_app_slug_redirects (
      old_slug VARCHAR(90) NOT NULL PRIMARY KEY, app_id INT UNSIGNED NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT fk_legal_slug_app FOREIGN KEY (app_id) REFERENCES legal_apps(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    // Primeira entrada é um RASCUNHO vazio, nunca um documento jurídico publicado.
    if((int)$db->query('SELECT COUNT(*) FROM legal_apps')->fetchColumn()===0){
        $db->beginTransaction();
        try {
            $q=$db->prepare('INSERT INTO legal_apps (name,slug,summary,description) VALUES (?,?,?,?)');
            $q->execute(['Uai Cortes','uaicortes','','']);
            igua_app_seed_default_pages($db,(int)$db->lastInsertId(),'Uai Cortes');
            $db->commit();
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }
}
function igua_app_find(PDO $db,int $id):?array {
    $q=$db->prepare('SELECT * FROM legal_apps WHERE id=? LIMIT 1');$q->execute([$id]);return $q->fetch()?:null;
}
function igua_app_find_page(PDO $db,int $id):?array {
    $q=$db->prepare('SELECT p.*,a.slug AS app_slug,a.name AS app_name,a.status AS app_status,a.developer_name,a.support_email,a.website_url,a.description AS app_description,a.logo_filename FROM legal_app_pages p JOIN legal_apps a ON a.id=p.app_id WHERE p.id=? LIMIT 1');
    $q->execute([$id]);return $q->fetch()?:null;
}
function igua_app_public(PDO $db,string $slug,string $page):?array {
    $q=$db->prepare("SELECT a.name,a.slug,a.summary,a.description,a.logo_filename,a.developer_name,a.support_email,a.website_url,
          p.slug AS page_slug,p.kind,p.updated_at AS draft_updated_at,r.title,r.body,r.seo_description,r.created_at AS published_updated_at
          FROM legal_apps a JOIN legal_app_pages p ON p.app_id=a.id
          JOIN legal_app_revisions r ON r.id=p.published_revision_id AND r.page_id=p.id
          WHERE a.slug=? AND a.status='published' AND p.slug=? AND p.status='published' LIMIT 1");
    $q->execute([$slug,$page]);return $q->fetch()?:null;
}
function igua_app_slug_alias(PDO $db,string $slug,string $page='inicio'):?string {
    $q=$db->prepare("SELECT a.slug FROM legal_app_slug_redirects al JOIN legal_apps a ON a.id=al.app_id JOIN legal_app_pages p ON p.app_id=a.id WHERE al.old_slug=? AND a.status='published' AND p.slug=? AND p.status='published' AND p.published_revision_id IS NOT NULL LIMIT 1");
    $q->execute([$slug,$page]);$found=$q->fetchColumn();return $found===false?null:(string)$found;
}
