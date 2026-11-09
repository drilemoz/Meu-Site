<?php
declare(strict_types=1);
/** Contrato de distribuição do instalador VPS; nenhum serviço real é executado. */
$root=dirname(__DIR__);
$install=(string)file_get_contents($root.'/deploy/aws/install.sh');
$nginx=(string)file_get_contents($root.'/deploy/aws/nginx_upstream.py');
$build=(string)file_get_contents($root.'/tools/build_source_package.py');
$update=(string)file_get_contents($root.'/tools/build_update_package.py');
$docs=(string)file_get_contents($root.'/docs/INSTALADOR_VPS_142.md');
$assert=static function(bool $condition,string $description):void {
    if(!$condition)throw new RuntimeException('Instalador VPS 242: '.$description);
};
$assert(str_contains($install,'--check')&&str_contains($install,'--ack-backup'),'inspeção e confirmação de backup');
$assert(str_contains($install,'running.json')&&str_contains($install,'request.json')&&str_contains($install,'.update-maintenance'),'bloqueio de estados pendentes');
$assert(str_contains($install,'"$SITE/public/uploads" -prune'),'exclusão de uploads das operações de código');
$assert(str_contains($install,'iguatama-deploy.timer')&&str_contains($install,'aws-deploy-check.php'),'worker com vistoria');
$assert(str_contains($install,'--nginx-conf')&&str_contains($nginx,'def inspect('),'verificação de Nginx dedicado');
$assert(str_contains($install,'CONFIG_CHANGED=1')&&str_contains($install,'BACKUP_DIR'),'recuperação das configurações');
$assert(str_contains($install,'php8.3-fpm-iguatamaonline.sock')&&str_contains($install,'www-data'),'pool isolado, nunca root');
$assert(!str_contains($install,'rm -rf')&&!str_contains($install,'chmod 0777'),'sem destruição ou escrita irrestrita');
$assert(str_contains($build,"'install.sh','nginx_upstream.py'"),'scripts incluídos somente no código-fonte completo');
$assert(!str_contains($update,'deploy/aws/install.sh'),'instalador não incluído no pacote de atualização');
$assert(str_contains($docs,'não é um migrador completo')||str_contains($docs,'**Não é um migrador completo:**'),'documentação diferencia migração de VPS e de dados');
$assert(trim((string)file_get_contents($root.'/VERSION'))==='1.0.142','versionamento correto');
echo "OK: VPS 242 distribuição, integridade, preflight, dados e documentação\n";
