<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$read=static fn(string $name):string=>(string)file_get_contents($root.'/'.$name);
function verify175(bool $ok,string $what):void {if(!$ok)throw new RuntimeException('1.0.75: '.$what);echo 'OK: '.$what.PHP_EOL;}
$view=$read('app/admin_views/routes/updates.php');
$js=$read('public/assets/js/updater-progress.js');
$dialogs=$read('public/assets/js/updater-dialogs.js');
$css=$read('public/assets/css/modules/09-editorial-admin.css');
verify175(str_contains($view,'data-updater-open="updater-upload-dialog"')&&str_contains($view,'aria-haspopup="dialog"'),'botão de enviar atualização na barra principal');
verify175(str_contains($view,'id="updater-upload-dialog"')&&str_contains($view,'aria-modal="true"'),'modal independente central');
verify175(str_contains($view,'data-updater-confirm-flow="manual"')&&str_contains($view,'data-updater-confirm-step hidden'),'confirmação isolada da etapa inicial');
verify175(str_contains($view,'data-updater-ack required disabled')&&str_contains($view,'name="confirm_password"')&&str_contains($view,'csrf_field()'),'senha, confirmação real e CSRF preservados');
verify175(substr_count($view,'data-updater-progress-form')===3,'progressos manual, remoto e simulação preservados');
verify175(!str_contains($view,'Instalar ZIP de atualização manualmente'),'bloco antigo de rolagem removido');
verify175(str_contains($js,'progress.loaded / progress.total')&&str_contains($js,"'Processando…'")&&str_contains($js,"bar.removeAttribute('value')"),'sem porcentagem fictícia para processamento PHP');
verify175(str_contains($js,'if (!manual) window.location.href = form.action'),'modal não redireciona automaticamente durante retorno');
verify175(str_contains($dialogs,"dialog.dataset.updaterBusy === '1'")&&str_contains($dialogs,"dialog.addEventListener('cancel'"),'não permite fechar modal durante operação');
verify175(str_contains($css,'.updater-upload-dialog')&&str_contains($read('public/assets/css/style.css'),'.updater-upload-dialog'),'CSS mobile sincronizado');
verify175($read('VERSION')==="1.0.75\n",'versão sincronizada');
