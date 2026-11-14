<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/news_editor.php';
require_once __DIR__.'/../app/backup.php';
function media_assert(bool $ok,string $name): void {if(!$ok)throw new RuntimeException('Falha: '.$name);echo "OK: $name\n";}
media_assert(media_valid_name('2026/10/'.str_repeat('a',32).'.webp'),'ano/mês e nome seguro');
media_assert(media_valid_name(str_repeat('a',32).'.jpg'),'fotografias legadas mantidas');
foreach(['../config.php','2026/13/'.str_repeat('a',32).'.jpg','2026/10/../x.jpg','2026/10/bad.php','a/2026/'.str_repeat('a',32).'.jpg','2026\\10\\'.str_repeat('a',32).'.jpg'] as $invalid)media_assert(!media_valid_name($invalid),'bloqueia caminho inválido');
media_assert(media_url('2026/10/'.str_repeat('b',32).'.webp')==='/uploads/2026/10/'.str_repeat('b',32).'.webp','URL preserva subpastas');
media_assert(media_url('../x.php')==='/assets/img/cidade.jpg','URL perigosa não é emitida');
media_assert(media_thumb('2026/10/'.str_repeat('b',32).'.jpg')==='/uploads/2026/10/'.str_repeat('b',32).'.jpg','miniatura ausente usa original');
media_assert(BACKUP_TABLES_V9[count(BACKUP_TABLES_V9)-1]==='media_library','catálogo no backup v9');
foreach([7=>BACKUP_TABLES_V7,8=>BACKUP_TABLES_V8,9=>BACKUP_TABLES_V9,10=>BACKUP_TABLES_V10,11=>BACKUP_TABLES_V11,12=>BACKUP_TABLES] as $version=>$tables){
    $items=array_fill_keys($tables,[]);$items['users']=[['id'=>1,'role'=>'admin','active'=>1]];
    $parsed=parse_backup(json_encode(['format'=>'iguanews-backup','version'=>$version,'tables'=>$items],JSON_THROW_ON_ERROR),'test.json');
    media_assert(array_keys($parsed['tables'])===BACKUP_TABLES,'backup v'.$version.' aceito');
}
media_assert(str_contains(file_get_contents(__DIR__.'/../app/admin_controllers/media.php'),'media_link_count'),'exclusão verifica referências');
media_assert(str_contains(file_get_contents(__DIR__.'/../app/backup.php'),'assets/images/'),'ZIP inclui mídia organizada');
echo "Mídias: verificações isoladas aprovadas; upload real e MariaDB pendentes.\n";
