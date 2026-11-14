<?php
declare(strict_types=1);
// Contract and regression guard for the progressive installer. No DB access required.
$setup=file_get_contents(__DIR__.'/../public/setup.php');
$js=file_get_contents(__DIR__.'/../public/assets/js/setup-wizard.js');
$upload=file_get_contents(__DIR__.'/../public/assets/js/setup-backup-upload.js');
$css=file_get_contents(__DIR__.'/../public/assets/css/modules/02-installer.css');
function stepOk233(bool $ok,string $name):void {static $n=0;$n++;if(!$ok)throw new RuntimeException('Falhou: '.$name);echo "OK {$n}: {$name}\n";}
stepOk233(str_contains($setup,"max(1,min(4,"),'quatro etapas no PHP');
foreach(["$".'step===1',"$".'step===2',"$".'step===3',"$".'step===4'] as $phase) stepOk233(str_contains($setup,$phase),'tela '.$phase);
stepOk233(str_contains($setup,'<input type="hidden" name="step" value="3">'),'modo escolhido em página independente');
stepOk233(str_contains($setup,'name="mode" value="fresh" required') && str_contains($setup,'name="mode" value="migrate" required'),'seleção explícita obrigatória');
stepOk233(str_contains($setup,"$".'ready?\'/setup.php?step=2\':\'#requisitos\''),'botão requisitos respeita verificação');
stepOk233(str_contains($setup,'name="portal_password_confirm"'),'senha de administrador confirmada no HTML');
stepOk233(str_contains($setup,"hash_equals($".'pass,(string)($_POST[\'portal_password_confirm\']??\'\'))'),'confirmação obrigatória no servidor');
stepOk233(str_contains($js,"dataset.visibilityReady === '1'"),'resumo nunca mostra senha após revelar');
stepOk233(str_contains($js,"input.setCustomValidity"),'comparação dinâmica de senhas');
stepOk233(str_contains($js,'Faltam ${missing}'),'contador de caracteres');
stepOk233(str_contains($js,'Fraca') && str_contains($js,'Média') && str_contains($js,'Forte'),'nível de senha');
stepOk233(str_contains($js,"input.type = visible ? 'text' : 'password'"),'mostrar e ocultar senha');
stepOk233(str_contains($js,'form.noValidate = true') && str_contains($js,'input.checkValidity()'),'etapas tratam validação nativa individualmente');
stepOk233(str_contains($js,'e.stopImmediatePropagation()'),'bloqueia envio parcial se campo inválido');
stepOk233(str_contains($upload,"fetch('/setup.php?step=4'"),'upload em partes usa etapa final correta');
stepOk233(str_contains($css,'.setup-guided') && str_contains($css,'.setup-top-actions'),'cartão central e ação topo');
