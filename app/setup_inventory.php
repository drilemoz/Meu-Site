<?php
declare(strict_types=1);
/** Tabelas conhecidas na revisão SQL 37. Lista fechada: nunca mexer em tabelas de outro projeto. */
function igua_setup_known_tables(): array {
    return [
        'ad_campaigns','ad_placements','admin_remember_tokens','analytics_events',
        'article_blocks','article_categories','article_drafts','article_import_logs',
        'article_media','article_revisions','article_slug_redirects','articles',
        'author_profiles','backup_logs','bus_departures','bus_import_queue',
        'bus_routes','bus_stops','categories','editorial_sources',
        'election_candidates','elections','media_library','settings','users',
        'legal_apps','legal_app_pages','legal_app_revisions','legal_app_slug_redirects',
    ];
}
/** Inventário exclusivamente de leitura. Nunca DROP, TRUNCATE ou sobrescrita automática. */
function igua_setup_database_inventory(PDO $db): array {
    $tables=$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $tables=array_values(array_map('strval',$tables));
    $known=igua_setup_known_tables();
    $unexpected=array_values(array_diff($tables,$known));
    if ($unexpected) return ['status'=>'foreign','tables'=>count($tables),'unexpected'=>count($unexpected),'populated'=>0,
        'detail'=>'Há tabelas que não pertencem à versão atual do Meu Site. Use outro banco; nenhum dado será alterado.'];
    if (!$tables) return ['status'=>'empty','tables'=>0,'unexpected'=>0,'populated'=>0,
        'detail'=>'Banco vazio e disponível para instalação nova.'];
    $populated=0; $counts=[];
    foreach ($tables as $name) {
        // $name foi validado contra uma lista literal fechada, nunca vindo livremente do usuário.
        $count=(int)$db->query('SELECT COUNT(*) FROM `'.$name.'`')->fetchColumn();
        $counts[$name]=$count;
        if ($count>0) $populated++;
    }
    if (!$populated) return ['status'=>'partial_empty','tables'=>count($tables),'unexpected'=>0,'populated'=>0,
        'detail'=>'Estrutura parcial conhecida, sem registros. A criação idempotente de tabelas pode prosseguir.'];
    if (count(array_diff($known,$tables))===0 && ($counts['users']??0)>=1 && ($counts['settings']??0)>0) {
        return ['status'=>'recoverable','tables'=>count($tables),'unexpected'=>0,'populated'=>$populated,
            'detail'=>'O banco possui tabelas e administrador de uma instalação anterior. É possível verificar a recuperação com a senha desse administrador, sem duplicar tabelas.'];
    }
    return ['status'=>'occupied','tables'=>count($tables),'unexpected'=>0,'populated'=>$populated,
        'detail'=>'O banco contém dados ou instalação parcial incompleta. Não sobrescrever. Faça backup e use outro banco para testes.'];
}
/** Recuperação restrita a banco completo da revisão atual com admin único ativo. */
function igua_setup_recovery_allowed(PDO $db): bool {
    $inventory=igua_setup_database_inventory($db);
    if ($inventory['status']!=='recoverable') return false;
    require_once __DIR__.'/schema_migrations.php';
    if (igua_schema_revision($db)!==IGUA_SCHEMA_REVISION) return false;
    $q=$db->prepare('SELECT COUNT(*) FROM settings WHERE name IN (?,?)');
    $q->execute(['site_name','seo_default_description']);
    return (int)$q->fetchColumn()===2;
}
function igua_setup_verify_recovery_admin(PDO $db, string $email, string $password): bool {
    if (!filter_var($email,FILTER_VALIDATE_EMAIL) || $password==='') return false;
    $s=$db->prepare("SELECT password_hash FROM users WHERE email=? AND role='admin' AND active=1 LIMIT 1");
    $s->execute([$email]);
    $hash=$s->fetchColumn();
    return is_string($hash) && password_verify($password,$hash);
}
