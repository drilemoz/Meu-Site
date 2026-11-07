<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/updater.php';
function assert192(bool $ok,string $what):void {if(!$ok)throw new RuntimeException('1.0.92: '.$what);}
$view=file_get_contents(__DIR__.'/../app/admin_views/routes/updates.php');
$admin=file_get_contents(__DIR__.'/../public/admin.php');
$controller=file_get_contents(__DIR__.'/../app/admin_controllers/updater.php');
$js=file_get_contents(__DIR__.'/../public/assets/js/updater-github.js');
assert192(str_contains($view,'id="updater-github-dialog"')&&str_contains($view,'id="updater-github-trigger"'),'consulta abre diálogo GitHub');
assert192(!str_contains($view,'>Releases ↗')&&!str_contains($view,'class="updater-quick-tools"'),'link Releases e cartões removidos da tela');
assert192(str_contains($view,'data-github-final')&&str_contains($view,'data-github-refresh'),'conclusão e atualização da página');
assert192(str_contains($admin,'updater_info')&&str_contains($admin,'updater_status')&&str_contains($admin,"if($".'action===\'updates\' && !request_post()'),'endpoints limitados a GET autenticado');
assert192(str_contains($controller,'session_write_close()')&&str_contains($controller,'session_start()'),'sessão liberada para consultar progresso');
assert192(str_contains($controller,"\$operation==='install_remote'")&&str_contains($controller,'confirm_password')&&str_contains($controller,'confirm_install'),'proteção de atualização mantida');
assert192(str_contains($js,'new FormData(form)')&&str_contains($js,'textContent')&&!str_contains($js,'innerHTML'),'notas tratadas como texto, senha e CSRF enviados pelo formulário');
assert192(igua_updater_progress_file(7,'invalid')===null,'token inválido não gera arquivo');
$token=str_repeat('a',32);$path=igua_updater_progress_file(87512,$token);
assert192(is_string($path)&&str_starts_with($path,IGUA_UPDATE_DIR.'/progress-'),'progresso privado nomeado por hash');
try{
    igua_updater_progress_write(87512,$token,'backup','Criando backup.');
    $value=igua_updater_progress_read(87512,$token);
    assert192(($value['stage']??'')==='backup'&&($value['message']??'')==='Criando backup.','estado real de backup persiste');
    assert192(igua_updater_progress_read(87513,$token)===null,'outro usuário não acessa o status');
    igua_updater_progress_write(87512,$token,'invalid','Mensagem indevida');
    assert192(igua_updater_progress_read(87512,$token)['stage']==='backup','estados não reconhecidos rejeitados');
}finally{if(is_string($path))@unlink($path);}
echo "OK: diálogo, consulta, autenticação, status privado e etapas da atualização 1.0.92\n";
