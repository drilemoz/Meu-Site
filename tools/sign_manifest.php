<?php
declare(strict_types=1);
// Uso CLI: php tools/sign_manifest.php /caminho/PRIVADO.key manifest.json > release.sig
if(PHP_SAPI!=='cli'||$argc!==3||!function_exists('sodium_crypto_sign_detached')){fwrite(STDERR,"Uso: php tools/sign_manifest.php CHAVE_PRIVADA manifest.json > release.sig\n");exit(2);}
$secret=trim((string)@file_get_contents($argv[1]));
$key=base64_decode($secret,true);
if(!$key||strlen($key)!==SODIUM_CRYPTO_SIGN_SECRETKEYBYTES){fwrite(STDERR,"Chave Ed25519 inválida (deve conter 64 bytes em base64).\n");exit(3);}
$data=@file_get_contents($argv[2]);
if(!is_string($data)){fwrite(STDERR,"Manifesto indisponível.\n");exit(4);}
fwrite(STDOUT,sodium_crypto_sign_detached($data,$key));
sodium_memzero($key);
