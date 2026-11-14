<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/requirements.php';
function ck148(bool $condition, string $label): void {
    if (!$condition) throw new RuntimeException('Falha v1.0.48: '.$label);
    echo 'OK: '.$label."\n";
}
$good=igua_public_files_diagnosis(IGUA_PUBLIC);
ck148($good['ok'], 'distribuição pública completa');
$root=sys_get_temp_dir().'/igua148_'.bin2hex(random_bytes(6));
mkdir($root,0700);
try {
    mkdir($root.'/assets/css',0700,true);
    mkdir($root.'/assets/js',0700,true);
    $essential=['index.php','setup.php','admin.php','router.php','assets/css/style.css','assets/js/news-editor.js'];
    foreach ($essential as $name) copy(IGUA_PUBLIC.'/'.$name,$root.'/'.$name);
    ck148(igua_public_files_diagnosis($root)['ok'], 'cópia pública completa de hospedagem');
    unlink($root.'/assets/css/style.css');
    $missing=igua_public_files_diagnosis($root);
    ck148(!$missing['ok'] && in_array('assets/css/style.css',$missing['missing'],true), 'CSS ausente bloqueia preparação incompleta');
    symlink(IGUA_PUBLIC.'/assets/css/style.css',$root.'/assets/css/style.css');
    ck148(!igua_public_files_diagnosis($root)['ok'], 'arquivo público ligado para fora da pasta não é aceito');
} finally {
    foreach (array_reverse($essential) as $name) { $path=$root.'/'.$name; if(is_link($path)||is_file($path)) unlink($path); }
    rmdir($root.'/assets/js'); rmdir($root.'/assets/css'); rmdir($root.'/assets'); rmdir($root);
}
$outside=sys_get_temp_dir().'/igua148-outside-'.bin2hex(random_bytes(5));
mkdir($outside,0700);
file_put_contents($outside.'/index.php','<?php');
$alias=IGUA_ROOT.'/www';
if (!file_exists($alias) && !is_link($alias)) {
    symlink($outside,$alias);
    try {
        ck148(igua_public_directory(IGUA_ROOT,[], 'www')!==realpath($outside),
            'dica CLI não pode redirecionar uploads para fora do projeto');
    } finally { unlink($alias); }
}
unlink($outside.'/index.php');rmdir($outside);
ck148(igua_php_size('1.5M')===1572864, 'decodifica limite em MiB');
ck148(igua_php_size('512K')===524288, 'decodifica limite em KiB');
ck148(igua_php_size('-1')===PHP_INT_MAX, 'sem limite via -1');
ck148(!igua_upload_limits_diagnosis('15M','15M')['ok'], 'POST sem margem não suporta backup máximo');
ck148(igua_upload_limits_diagnosis('15M','16M')['ok'], 'POST com margem aceita backup máximo');
ck148(!igua_upload_limits_diagnosis('8M','32M')['ok'], 'limite individual ainda é verificado');
ck148(igua_post_exceeds_limit(['CONTENT_LENGTH'=>'17000000'],'16M'), 'identifica formulário descartado pelo PHP');
ck148(!igua_post_exceeds_limit(['CONTENT_LENGTH'=>'16000000'],'16M'), 'aceita corpo abaixo do limite');
ck148(!igua_post_exceeds_limit(['CONTENT_LENGTH'=>'x1700'],'16M'), 'rejeita valor inválido sem confiar no cabeçalho');
$requirement=igua_requirements();
ck148((bool)array_filter($requirement,static fn(array $row): bool=> $row['id']==='public_files' && $row['required'] && $row['ok']), 'integridade da pasta pública é requisito obrigatório');
$setup=file_get_contents(IGUA_PUBLIC.'/setup.php');
ck148(str_contains($setup,'igua_post_exceeds_limit()'), 'wizard informa POST recusado antes do CSRF');
$health=file_get_contents(IGUA_ROOT.'/app/health.php');
ck148(str_contains($health,'igua_public_files_diagnosis(IGUA_PUBLIC)'), 'Central de Saúde também verifica layout');
echo "Verificações de compatibilidade híbrida v1.0.48 aprovadas.\n";
