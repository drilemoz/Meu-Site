<?php
declare(strict_types=1);
/** Reproduz o requisito do CI: HTTPS simulado e headers ainda disponíveis no CLI. */
require_once __DIR__.'/../app/auth_remember.php';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['SERVER_NAME']='127.0.0.1';
unset($_SERVER['HTTPS']);
if (igua_remember_is_secure_transport()) throw new RuntimeException('CLI HTTP não deve autorizar login persistente.');
ob_start();
echo "Saída de diagnóstico anterior ao cookie (bufferizada).\n";
$_SERVER['HTTPS']='on';
if (!igua_remember_is_secure_transport() || headers_sent()) throw new RuntimeException('HTTPS simulado ou buffers inválidos.');
if (!setcookie('meusite_ci_probe','ok',igua_remember_cookie_options(time()+60))) {
    throw new RuntimeException('setcookie recusado em HTTPS simulado com buffer ativo.');
}
if (!igua_remember_cookie_options(time()+60)['secure'] || !igua_remember_cookie_options(time()+60)['httponly']) {
    throw new RuntimeException('Flags de segurança do cookie inválidas.');
}
ob_end_clean();
unset($_SERVER['HTTPS']);
if (igua_remember_is_secure_transport()) throw new RuntimeException('HTTPS simulado vazou após o teste.');
echo "OK CLI: HTTPS simulado, headers preservados e setcookie aceito.\n";
