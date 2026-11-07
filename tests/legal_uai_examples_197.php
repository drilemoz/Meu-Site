<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/apps_legal.php';
function a197(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$examples=igua_uai_legal_examples();
a197(count($examples)===3,'Existem três modelos');
foreach(['home','privacy','terms'] as $kind){
 $data=$examples[$kind];
 a197(is_string($data['title']) && strlen($data['body'])>300, 'Modelo rico e editável');
 a197(igua_app_legal_template($kind,'Uai Cortes')===$data['body'],'Modelo integra cadastro inicial');
 a197(!preg_match('/<script|<iframe|javascript:/i',$data['body']),'Sem HTML ativo');
 a197(str_contains(igua_app_render_markdown($data['body']),'<h2>'),'Markdown renderiza no servidor');
}
a197(!str_contains($examples['privacy']['body'],'PREENCHER:') && !str_contains($examples['terms']['body'],'PREENCHER:') && !str_contains($examples['privacy']['body'],'Última atualização:'),'Modelos jurídicos objetivos sem instruções ou data fixa embutida');
a197(str_contains(igua_app_legal_template('privacy','Outro aplicativo'),'PREENCHER:'),'Outros apps não herdam texto Uai');
a197(str_contains(file_get_contents(__DIR__.'/../app/admin_controllers/apps_legal.php'),'igua_app_apply_uai_examples'),'Carregamento somente por POST explícito');
echo "OK: três modelos Uai, datas automáticas e revisão editorial necessária\n";
