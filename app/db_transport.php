<?php
declare(strict_types=1);
/** Configura TLS com verificação do certificado, sem aceitar caminho arbitrário do formulário. */
function igua_mysql_tls_options(bool $enabled, ?string $storage=null): array {
    if(!$enabled)return [];
    $storage ??= defined('IGUA_STORAGE') ? IGUA_STORAGE : dirname(__DIR__).'/storage';
    $ca=$storage.'/mysql-ca.pem';
    if(!is_file($ca)||is_link($ca)||!is_readable($ca)||filesize($ca)>128*1024
        ||!defined('PDO::MYSQL_ATTR_SSL_CA')||!defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')){
        throw new DomainException('TLS MySQL requer PDO MySQL com verificação de certificado e storage/mysql-ca.pem válido.');
    }
    $contents=(string)file_get_contents($ca);
    if(!str_contains($contents,'-----BEGIN CERTIFICATE-----'))throw new DomainException('Arquivo CA do MySQL inválido.');
    return [constant('PDO::MYSQL_ATTR_SSL_CA')=>$ca,constant('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')=>true];
}
