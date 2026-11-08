<?php
declare(strict_types=1);
function install_schema(PDO $db,bool $recordRevision=true): void {
    $db->exec("CREATE TABLE IF NOT EXISTS users (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE,
      password_hash VARCHAR(255) NOT NULL, role ENUM('admin','editor','author') NOT NULL DEFAULT 'admin', active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(75) NOT NULL, slug VARCHAR(90) NOT NULL UNIQUE,
      icon VARCHAR(30) NOT NULL DEFAULT '📰', color VARCHAR(20) NOT NULL DEFAULT '#1260e6',
      sort_order INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS articles (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      category_id INT UNSIGNED NULL, author_id INT UNSIGNED NULL,
      title VARCHAR(220) NOT NULL, slug VARCHAR(240) NOT NULL UNIQUE,
      summary VARCHAR(480) NOT NULL DEFAULT '', body MEDIUMTEXT NOT NULL,
      image VARCHAR(150) DEFAULT NULL,
      image_caption VARCHAR(360) NOT NULL DEFAULT '', image_credit VARCHAR(180) NOT NULL DEFAULT '',
      source_name VARCHAR(160) NOT NULL DEFAULT '', source_url VARCHAR(600) NOT NULL DEFAULT '',
      author_profile_id INT UNSIGNED NULL, source_id INT UNSIGNED NULL,
      seo_title VARCHAR(220) NOT NULL DEFAULT '', seo_description VARCHAR(320) NOT NULL DEFAULT '',
      status ENUM('draft','published') NOT NULL DEFAULT 'draft',
      featured TINYINT(1) NOT NULL DEFAULT 0, is_demo TINYINT(1) NOT NULL DEFAULT 0,
      views INT UNSIGNED NOT NULL DEFAULT 0,
      published_at DATETIME NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_list (status,published_at), INDEX idx_category (category_id,status,published_at),
      INDEX idx_public_featured (status,featured,published_at,id),
      INDEX idx_public_popular (status,views,published_at,id),
      CONSTRAINT fk_article_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
      CONSTRAINT fk_article_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
      name VARCHAR(80) NOT NULL PRIMARY KEY, value TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS backup_logs (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      filename VARCHAR(230) NOT NULL, bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
      status VARCHAR(32) NOT NULL DEFAULT 'ok', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    migrate_editorial_schema($db);
    migrate_bus_schema($db);
    require_once __DIR__.'/ads.php';
    migrate_ads_schema($db);
    require_once __DIR__.'/news_editor.php';
    migrate_news_schema($db);
    require_once __DIR__.'/article_workflow.php';
    migrate_article_slug_schema($db);
    require_once __DIR__.'/analytics.php';
    migrate_analytics_schema($db);
    require_once __DIR__.'/auth_remember.php';
    igua_remember_schema($db);
    require_once __DIR__.'/elections/schema.php';
    meu_elections_schema($db);
    require_once __DIR__.'/apps_legal.php';
    igua_migrate_apps_schema($db);
    require_once __DIR__.'/schema_migrations.php';
    if($recordRevision)igua_record_schema_revision($db);
}

/** A migração só acrescenta campos; nunca destrói imagens nem notícias. */
function migrate_article_fields(PDO $db): array {
    $wanted = [
      'image_caption' => "VARCHAR(360) NOT NULL DEFAULT ''",
      'image_credit' => "VARCHAR(180) NOT NULL DEFAULT ''",
      'source_name' => "VARCHAR(160) NOT NULL DEFAULT ''",
      'source_url' => "VARCHAR(600) NOT NULL DEFAULT ''"
    ];
    $existing = array_column($db->query('SHOW COLUMNS FROM articles')->fetchAll(), 'Field');
    $added=[];
    foreach ($wanted as $field=>$definition) {
        if (!in_array($field,$existing,true)) {
            $db->exec("ALTER TABLE articles ADD COLUMN `$field` $definition");
            $added[]=$field;
        }
    }
    return $added;
}
/** Acervo de referência: 10 textos editoriais por cada uma das 10 editorias.
 *  Identificado como amostra, sempre com fonte consultável e aviso de que não é reportagem local.
 *  PODE SUBSTITUIR apenas registros marcados como amostra; nunca altera matérias próprias.
 */
function seed_initial_categories(PDO $db): void {
    $cats = [
      ['Geral','geral','◉','#53647d'],['Cidade','cidade','⌂','#0964ed'],
      ['Política','politica','▤','#1356c4'],['Eventos','eventos','▣','#ef4662'],
      ['Esportes','esportes','◉','#139757'],['Empregos','empregos','▧','#325fd8'],
      ['Saúde','saude','✚','#20a8a8'],['Educação','educacao','▰','#2eaf83'],
      ['Segurança','seguranca','⬡','#f18a21'],['Cultura','cultura','✿','#ec5680']
    ];
    $insertCat=$db->prepare('INSERT INTO categories (name,slug,icon,color,sort_order) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE slug=slug');
    foreach ($cats as $i=>$cat) $insertCat->execute([...$cat,$i]);
}

function seed_demo(PDO $db, int $uid, bool $replaceSamples=false): array {
    seed_initial_categories($db);
    $ids=$db->query('SELECT slug,id FROM categories')->fetchAll(PDO::FETCH_KEY_PAIR);
    // CREATE/ALTER TABLE provoca COMMIT implícito no MariaDB. No instalador,
    // install_schema() já realizou as migrações antes da transação do administrador.
    // Não executar DDL dentro de uma transação de criação de conteúdo.
    if (!$db->inTransaction()) {
        migrate_article_fields($db);
        migrate_editorial_schema($db);
    }
    $news=json_decode((string)file_get_contents(__DIR__.'/sample_news.json'),true,512,JSON_THROW_ON_ERROR);
    if (!is_array($news) || count($news)!==100) throw new RuntimeException('Arquivo de notícias de amostra incompleto.');
    $ownTransaction = !$db->inTransaction();
    if ($ownTransaction) $db->beginTransaction();
    try {
        if ($replaceSamples) {
            $db->exec('DELETE FROM article_drafts WHERE article_id IN (SELECT id FROM articles WHERE is_demo=1)');
            $db->exec('DELETE FROM articles WHERE is_demo=1');
        }
        // Vincula referências do acervo ao catálogo, sem perder os textos originais.
        $sourceCache=[];
        $findSource=$db->prepare('SELECT id FROM editorial_sources WHERE name=? AND url=? LIMIT 1');
        $addSource=$db->prepare('INSERT INTO editorial_sources (name,url) VALUES (?,?)');
        $find=$db->prepare('SELECT id FROM articles WHERE slug=? LIMIT 1');
        $save=$db->prepare("INSERT INTO articles (category_id,author_id,title,slug,summary,body,image,image_caption,image_credit,source_name,source_url,source_id,status,featured,is_demo,published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'published',?,1,?)");
        $count=0;
        foreach ($news as $a) {
            if (!isset($ids[$a['category']])) throw new RuntimeException('Categoria de amostra inválida.');
            $slug=slug($a['title']);$find->execute([$slug]);
            if ($find->fetchColumn()) continue;
            $sourceKey=$a['source_name']."\0".$a['source_url'];
            if(!isset($sourceCache[$sourceKey])){
                $findSource->execute([$a['source_name'],$a['source_url']]);$sid=$findSource->fetchColumn();
                if(!$sid){$addSource->execute([$a['source_name'],$a['source_url']]);$sid=$db->lastInsertId();}
                $sourceCache[$sourceKey]=(int)$sid;
            }
            $save->execute([$ids[$a['category']],$uid,$a['title'],$slug,$a['summary'],$a['body'],$a['image'],$a['image_caption'],$a['image_credit'],$a['source_name'],$a['source_url'],$sourceCache[$sourceKey],$a['featured'],$a['published_at']]);
            ++$count;
        }
        if ($ownTransaction) $db->commit();
        return ['inserted'=>$count,'total'=>count($news)];
    } catch(Throwable $e) {if($ownTransaction && $db->inTransaction())$db->rollBack();throw $e;}
}

/** Evolução incremental e idempotente para instalações 1.0.0–1.0.3. Não altera dados existentes. */
function migrate_editorial_schema(PDO $db): array {
    $db->exec("CREATE TABLE IF NOT EXISTS author_profiles (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(120) NOT NULL, bio VARCHAR(500) NOT NULL DEFAULT '',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS editorial_sources (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(160) NOT NULL, url VARCHAR(600) NOT NULL DEFAULT '',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $changes=[];
    $wanted=[
      'users'=>[
        'role'=>"ENUM('admin','editor','author') NOT NULL DEFAULT 'admin'",
        'active'=>"TINYINT(1) NOT NULL DEFAULT 1"
      ],
      'articles'=>[
        'author_profile_id'=>'INT UNSIGNED NULL',
        'source_id'=>'INT UNSIGNED NULL',
        'seo_title'=>"VARCHAR(220) NOT NULL DEFAULT ''",
        'seo_description'=>"VARCHAR(320) NOT NULL DEFAULT ''"
      ]
    ];
    foreach ($wanted as $table=>$columns) {
        $existing=array_column($db->query("SHOW COLUMNS FROM `$table`")->fetchAll(),'Field');
        foreach ($columns as $field=>$definition) {
            if (!in_array($field,$existing,true)) {
                $db->exec("ALTER TABLE `$table` ADD COLUMN `$field` $definition");
                $changes[]="$table.$field";
            }
        }
    }
    // Importação única dos créditos já existentes, sem modificar as referências textuais.
    $seen=$db->query("SELECT value FROM settings WHERE name='editorial_catalog_imported'")->fetchColumn();
    if($seen===false){
        $references=$db->query("SELECT DISTINCT source_name,source_url FROM articles WHERE source_name<>'' AND source_id IS NULL")->fetchAll();
        $look=$db->prepare('SELECT id FROM editorial_sources WHERE name=? AND url=? LIMIT 1');
        $add=$db->prepare('INSERT INTO editorial_sources (name,url) VALUES (?,?)');
        $link=$db->prepare('UPDATE articles SET source_id=? WHERE source_name=? AND source_url=? AND source_id IS NULL');
        foreach($references as $r){
            $look->execute([$r['source_name'],$r['source_url']]);$id=$look->fetchColumn();
            if(!$id){$add->execute([$r['source_name'],$r['source_url']]);$id=$db->lastInsertId();}
            $link->execute([(int)$id,$r['source_name'],$r['source_url']]);
        }
        $db->prepare('INSERT INTO settings (name,value) VALUES (?,?)')->execute(['editorial_catalog_imported','1']);
    }
    // Conserva as contas existentes como administradores para não bloquear o proprietário.
    return $changes;
}

/** Horários de ônibus: aditivo, idempotente e sem linhas fictícias. */
function migrate_bus_schema(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS bus_routes (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(160) NOT NULL, company VARCHAR(160) NOT NULL DEFAULT '',
      notes VARCHAR(500) NOT NULL DEFAULT '', active TINYINT(1) NOT NULL DEFAULT 1,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS bus_stops (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      route_id INT UNSIGNED NOT NULL,
      city VARCHAR(100) NOT NULL, location_label VARCHAR(150) NOT NULL DEFAULT '',
      stop_order SMALLINT UNSIGNED NOT NULL, minutes_from_start INT UNSIGNED NOT NULL,
      UNIQUE KEY uq_bus_stop_order (route_id,stop_order),
      INDEX idx_bus_stop_city (city),
      CONSTRAINT fk_bus_stop_route FOREIGN KEY (route_id) REFERENCES bus_routes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS bus_departures (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      route_id INT UNSIGNED NOT NULL, start_time TIME NOT NULL,
      days_mask TINYINT UNSIGNED NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1,
      CONSTRAINT fk_bus_departure_route FOREIGN KEY (route_id) REFERENCES bus_routes(id) ON DELETE CASCADE,
      INDEX idx_bus_departure_route (route_id,active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $fields=array_column($db->query('SHOW COLUMNS FROM bus_routes')->fetchAll(), 'Field');
    $wanted=[
        'source_type'=>"ENUM('manual','der_mg','antt') NOT NULL DEFAULT 'manual'",
        'source_key'=>"VARCHAR(96) DEFAULT NULL",
        'source_url'=>"VARCHAR(750) NOT NULL DEFAULT ''",
        'sync_mode'=>"ENUM('fixed','auto') NOT NULL DEFAULT 'fixed'",
        'last_source_update'=>"DATETIME DEFAULT NULL",
        'fare_amount'=>"DECIMAL(9,2) DEFAULT NULL"
    ];
    foreach($wanted as $name=>$definition)if(!in_array($name,$fields,true))$db->exec("ALTER TABLE bus_routes ADD COLUMN `$name` $definition");
    $indexes=array_column($db->query('SHOW INDEX FROM bus_routes')->fetchAll(),'Key_name');
    if(!in_array('uq_bus_source',$indexes,true))$db->exec('ALTER TABLE bus_routes ADD UNIQUE KEY uq_bus_source (source_type,source_key)');
    $stops=array_column($db->query('SHOW COLUMNS FROM bus_stops')->fetchAll(),'Field');
    // importações oficiais podem NÃO ter hora de chegada: NULL indica desconhecido.
    $offset=$db->query("SHOW COLUMNS FROM bus_stops LIKE 'minutes_from_start'")->fetch();
    if($offset && strtoupper((string)$offset['Null'])==='NO')$db->exec('ALTER TABLE bus_stops MODIFY minutes_from_start INT UNSIGNED DEFAULT NULL');
    $db->exec("CREATE TABLE IF NOT EXISTS bus_import_queue (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      source ENUM('der_mg','antt') NOT NULL, external_key VARCHAR(96) NOT NULL,
      payload MEDIUMTEXT NOT NULL, source_url VARCHAR(750) NOT NULL DEFAULT '',
      status ENUM('pending','approved','ignored') NOT NULL DEFAULT 'pending',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_bus_queue_source (source,external_key), INDEX idx_bus_queue_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
