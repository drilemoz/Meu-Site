#!/usr/bin/env bash
# IguatamaOnline - preparação inicial/reexecutável da conta de deploy em VPS Ubuntu.
# Não instala código, não modifica banco, não apaga storage/uploads e não usa root no PHP-FPM.
set -Eeuo pipefail
umask 027

SITE='/var/www/projetos/iguatamaonline'
DEPLOY_USER='iguanews-deploy'
WEB_USER='www-data'
PHP_VERSION='8.3'
MODE='apply'
NGINX_OVERRIDE=''
ACK_BACKUP=0
BACKUP_DIR=''
FPM_POOL=''
NGINX_CONF=''
FPM_PREEXISTED=0
NGINX_PREEXISTED=0
CONFIG_CHANGED=0
COMPLETED=0

usage() {
  cat <<'HELP'
Instalador VPS do IguatamaOnline (Ubuntu 24.04 / Nginx / PHP-FPM 8.3)
Uso: sudo bash deploy/aws/install.sh [--check] [--nginx-conf /etc/nginx/sites-available/...] [--ack-backup]
  (sem opções)     Prepara conta/permissões, pool FPM exclusivo, Nginx e systemd
  --check          Inspeciona sem alterar arquivos, usuários ou serviços
  --nginx-conf     Indica o vhost dedicado quando há mais de um candidato
  --ack-backup     Confirma cópias externas já verificadas (para automação sem prompt)
O instalador não importa o banco, não muda DNS/TLS e não configura servidores compartilhados.
Se não puder identificar com segurança o vhost, interrompe antes de modificar a VPS.
HELP
}
log(){ printf '[IguatamaOnline] %s\n' "$*"; }
die(){ printf '[IguatamaOnline] ERRO: %s\n' "$*" >&2; exit 1; }
for arg in "$@"; do
  case "$arg" in
    --check) MODE='check' ;;
    --ack-backup) ACK_BACKUP=1 ;;
    --help|-h) usage;exit 0 ;;
    *) ;; # As opções com argumento são lidas abaixo.
  esac
done
while (($#)); do
  case "$1" in
    --check|--ack-backup) shift ;;
    --nginx-conf) (($#>=2)) || die 'Faltou caminho após --nginx-conf'; NGINX_OVERRIDE="$2"; shift 2 ;;
    --help|-h) shift ;;
    *) die "Opção desconhecida: $1" ;;
  esac
done

# Não desfaz proprietários após falha; reverte SOMENTE a configuração Nginx/FPM
# a partir de cópias locais privadas, preservando os dados de aplicação.
cleanup(){
  local status=$?
  trap - EXIT
  if ((status != 0 && CONFIG_CHANGED == 1)) && [[ -n "$BACKUP_DIR" ]]; then
    log 'Falha detectada: restaurando configuração anterior de Nginx/PHP-FPM.'
    if ((FPM_PREEXISTED)); then cp -p "$BACKUP_DIR/pool.conf" "$FPM_POOL" || true
    else rm -f -- "$FPM_POOL" || true; fi
    if ((NGINX_PREEXISTED)); then cp -p "$BACKUP_DIR/nginx.conf" "$NGINX_CONF" || true; fi
    /usr/sbin/php-fpm"$PHP_VERSION" -t >/dev/null 2>&1 && systemctl restart "php$PHP_VERSION-fpm" || true
    nginx -t >/dev/null 2>&1 && systemctl reload nginx || true
    log 'O timer não foi habilitado por esta tentativa; revise a saída e os backups.'
  fi
  exit "$status"
}
trap cleanup EXIT

[[ -d "$SITE" && -f "$SITE/VERSION" && -f "$SITE/bin/aws-deploy-check.php" ]] || die 'Instale o ZIP de código-fonte completo antes (VERSION e bin/aws-deploy-check.php).'
[[ "$(readlink -f -- "$SITE")" == "$SITE" ]] || die 'Caminho do site não pode ser um link simbólico.'
for path in "$SITE/app" "$SITE/public" "$SITE/storage" "$SITE/storage/updater" "$SITE/storage/config.php" "$SITE/public/uploads"; do
  [[ ! -L "$path" ]] || die "Link simbólico proibido em área crítica: $path"
done
[[ -d "$SITE/app" && -d "$SITE/public" && -d "$SITE/storage" && -f "$SITE/storage/config.php" ]] || die 'Estrutura do portal incompleta; não modifique dados persistentes.'
[[ -f "$SITE/deploy/aws/iguatama-deploy.service" && -f "$SITE/deploy/aws/iguatama-deploy.timer" && -f "$SITE/deploy/aws/nginx_upstream.py" ]] || die 'Unidades systemd ausentes no fonte completo.'
for state in "$SITE/storage/.update-maintenance" "$SITE/storage/updater/vps/private/maintenance" "$SITE/storage/updater/vps/running.json" "$SITE/storage/updater/vps/request.json"; do
  [[ ! -e "$state" && ! -L "$state" ]] || die "Atualização pendente/interrompida em $state. Investigue antes de instalar."
done
if [[ -n "$(find "$SITE/app" "$SITE/public" -path "$SITE/public/uploads" -prune -o -type l -print -quit)" ]]; then
  die 'Há links simbólicos no código de app/public. Verifique-os manualmente antes de alterar proprietários.'
fi

[[ -r /etc/os-release ]] || die 'Sistema operacional não identificado.'
# shellcheck disable=SC1091
source /etc/os-release
[[ "${ID:-}" == ubuntu ]] || die 'Este instalador suporta somente Ubuntu; não altera sistemas desconhecidos.'
for tool in nginx systemctl systemd-analyze usermod useradd getent python3 rsync stat runuser; do
  command -v "$tool" >/dev/null || die "Falta $tool. Instale as dependências pelo apt antes de continuar."
done
[[ -x "/usr/bin/php$PHP_VERSION" && -x "/usr/sbin/php-fpm$PHP_VERSION" ]] || die "PHP $PHP_VERSION CLI e PHP-FPM devem estar instalados."
[[ -f "/lib/systemd/system/php$PHP_VERSION-fpm.service" || -f "/usr/lib/systemd/system/php$PHP_VERSION-fpm.service" ]] || die 'Serviço PHP-FPM não identificado.'
getent passwd "$WEB_USER" >/dev/null || die "Usuário web $WEB_USER inexistente."
getent group "$WEB_USER" >/dev/null || die "Grupo web $WEB_USER inexistente."
for ext in zip pdo_mysql curl posix; do
  /usr/bin/php"$PHP_VERSION" -r 'exit(extension_loaded($argv[1]) ? 0 : 1);' "$ext" || die "Extensão PHP ausente: $ext"
done

# Descobre apenas vhosts ATIVOS e com uma única raiz deste projeto.
# Não toca em arquivo com outras raízes, múltiplos FastCGI ou configuração não reconhecida.
declare -a candidates=()
if [[ -n "$NGINX_OVERRIDE" ]]; then
  [[ -f "$NGINX_OVERRIDE" ]] || die 'Arquivo Nginx informado não existe.'
  candidates+=("$(readlink -f -- "$NGINX_OVERRIDE")")
else
  shopt -s nullglob
  for config in /etc/nginx/sites-enabled/* /etc/nginx/conf.d/*.conf; do
    [[ -f "$config" ]] || continue
    if grep -Fq -- "$SITE/public" "$config"; then
      real="$(readlink -f -- "$config")"
      [[ " ${candidates[*]} " == *" $real "* ]] || candidates+=("$real")
    fi
  done
fi
((${#candidates[@]} == 1)) || die "Foi encontrado ${#candidates[@]} vhost(s). Use --nginx-conf com o arquivo exclusivo do portal."
NGINX_CONF="${candidates[0]}"
# Um caminho informado manualmente ainda precisa ser um vhost ativo.
active=0
shopt -s nullglob
for candidate in /etc/nginx/sites-enabled/* /etc/nginx/conf.d/*.conf; do
  [[ -f "$candidate" ]] || continue
  if [[ "$(readlink -f -- "$candidate")" == "$NGINX_CONF" ]]; then active=1; break; fi
done
((active == 1)) || die 'O arquivo Nginx não está em um vhost ativo.'
[[ "$NGINX_CONF" == /etc/nginx/* && -f "$NGINX_CONF" && ! -L "$NGINX_CONF" ]] || die 'Vhost precisa ser um arquivo real sob /etc/nginx.'
python3 "$SITE/deploy/aws/nginx_upstream.py" \
  --config "$NGINX_CONF" --site-public "$SITE/public" --php-version "$PHP_VERSION" \
  || die 'Vhost compartilhado, ambíguo ou sem fastcgi_pass direto. Nada será modificado.'
FPM_POOL="/etc/php/$PHP_VERSION/fpm/pool.d/iguatamaonline.conf"
[[ -d "$(dirname "$FPM_POOL")" ]] || die 'Diretório de pools PHP-FPM ausente.'
if [[ -f "$FPM_POOL" ]] && ! grep -Fq '; Gerenciado por deploy/aws/install.sh do IguatamaOnline' "$FPM_POOL"; then
  die 'Pool iguatamaonline.conf existe e não foi criado pelo instalador. Não sobrescrever.'
fi

if command -v mariadb >/dev/null 2>&1; then
  log 'Cliente MariaDB identificado (o instalador NÃO toca no banco).'
else
  log 'Cliente MariaDB não detectado: verifique se o banco é remoto e faça a migração separadamente.'
fi
log "Site: $SITE | Nginx: $NGINX_CONF | pool: $FPM_POOL"
if [[ "$MODE" == check ]]; then
  log 'CHECK somente leitura: pré-requisitos essenciais e vhost seguro identificados.'
  if id "$DEPLOY_USER" >/dev/null 2>&1; then
    log "Conta $DEPLOY_USER existe."
  else
    log "Conta $DEPLOY_USER será criada."
  fi
  log 'Nenhum arquivo, conta, banco ou serviço foi modificado.'
  exit 0
fi
[[ "$EUID" -eq 0 ]] || die 'Execute com sudo para aplicar configurações.'
if ((ACK_BACKUP == 0)); then
  [[ -t 0 ]] || die 'Sem terminal interativo: informe --ack-backup após verificar backup externo do banco, storage e uploads.'
  printf 'Confirma backup externo verificado de banco, storage e uploads? Digite BACKUP: '
  read -r answer
  [[ "$answer" == BACKUP ]] || die 'Sem confirmação de backup. Nenhuma alteração realizada.'
fi
# Verifica que o serviço não está no meio de uma execução.
if systemctl is-active --quiet iguatama-deploy.service; then
  die 'Serviço de implantação está executando. Aguarde e revise os estados antes de reconfigurar.'
fi
BACKUP_DIR="/var/backups/iguatamaonline-installer/$(date +%Y%m%d-%H%M%S)-$$"
install -d -m 0700 "$BACKUP_DIR"
if [[ -e "$FPM_POOL" ]]; then cp -p "$FPM_POOL" "$BACKUP_DIR/pool.conf"; FPM_PREEXISTED=1; fi
cp -p "$NGINX_CONF" "$BACKUP_DIR/nginx.conf"; NGINX_PREEXISTED=1
log "Configurações originais preservadas em $BACKUP_DIR"
# Evita uma execução agendada concorrente durante o ajuste de permissões.
systemctl stop iguatama-deploy.timer 2>/dev/null || true
if systemctl is-active --quiet iguatama-deploy.service; then
  die 'Worker iniciou enquanto o timer era interrompido. Verifique estado e reexecute.'
fi

if ! id "$DEPLOY_USER" >/dev/null 2>&1; then
  useradd --system --user-group --no-create-home --home-dir /nonexistent --shell /usr/sbin/nologin "$DEPLOY_USER"
fi
usermod -aG "$WEB_USER" "$DEPLOY_USER"

# O usuário web continua sem permissão para escrever código; nunca usar chmod 777.
chown "$DEPLOY_USER:$DEPLOY_USER" "$SITE"
chmod 0755 "$SITE"
chown -R "$DEPLOY_USER:$DEPLOY_USER" "$SITE/app" "$SITE/bin"
find "$SITE/app" "$SITE/bin" -type d -exec chmod 0755 {} +
find "$SITE/app" "$SITE/bin" -type f -exec chmod 0644 {} +
# Exclui toda a árvore de uploads: fotos e ACL do servidor permanecem intactas.
find "$SITE/public" -path "$SITE/public/uploads" -prune -o -type d -exec chown "$DEPLOY_USER:$DEPLOY_USER" {} +
find "$SITE/public" -path "$SITE/public/uploads" -prune -o -type f -exec chown "$DEPLOY_USER:$DEPLOY_USER" {} +
find "$SITE/public" -path "$SITE/public/uploads" -prune -o -type d -exec chmod 0755 {} +
find "$SITE/public" -path "$SITE/public/uploads" -prune -o -type f -exec chmod 0644 {} +
chown "$DEPLOY_USER:$DEPLOY_USER" "$SITE/VERSION"
chmod 0644 "$SITE/VERSION"
chown "$WEB_USER:$WEB_USER" "$SITE/storage"
chmod 0750 "$SITE/storage"
chown "$WEB_USER:$WEB_USER" "$SITE/storage/config.php"
chmod 0640 "$SITE/storage/config.php"
for dir in "$SITE/storage/updater" "$SITE/storage/updater/vps" "$SITE/storage/updater/vps/private" "$SITE/storage/updater/jobs" "$SITE/storage/backups"; do
  [[ ! -L "$dir" ]] || die "Diretório privado não pode ser symlink: $dir"
done
install -d -o "$WEB_USER" -g "$WEB_USER" -m 2770 "$SITE/storage/backups"
install -d -o "$DEPLOY_USER" -g "$WEB_USER" -m 3770 "$SITE/storage/updater"
install -d -o "$DEPLOY_USER" -g "$WEB_USER" -m 3770 "$SITE/storage/updater/vps"
install -d -o "$DEPLOY_USER" -g "$WEB_USER" -m 2750 "$SITE/storage/updater/jobs"
install -d -o "$DEPLOY_USER" -g "$WEB_USER" -m 2750 "$SITE/storage/updater/vps/private"
for lock in "$SITE/storage/updater/vps/.dispatch.lock" "$SITE/storage/updater/vps/private/.code.lock"; do
  [[ ! -L "$lock" ]] || die "Lock não pode ser symlink: $lock"
  touch "$lock"
  chown "$DEPLOY_USER:$WEB_USER" "$lock"
done
chmod 0660 "$SITE/storage/updater/vps/.dispatch.lock"
chmod 0600 "$SITE/storage/updater/vps/private/.code.lock"
# Não remove jobs existentes nem estados anteriores; journals são preservados.
if [[ -d "$SITE/storage/updater/jobs" ]]; then
  chown -R "$DEPLOY_USER:$WEB_USER" "$SITE/storage/updater/jobs"
  find "$SITE/storage/updater/jobs" -type d -exec chmod 2750 {} +
fi
if [[ -f "$SITE/storage/updater/last.json" && ! -L "$SITE/storage/updater/last.json" ]]; then
  chown "$DEPLOY_USER:$WEB_USER" "$SITE/storage/updater/last.json"
  chmod 0640 "$SITE/storage/updater/last.json"
fi

# Pool dedicado evita alterar www.conf e protege o site de testes no mesmo PHP-FPM.
CONFIG_CHANGED=1
cat > "$FPM_POOL" <<'POOL'
; Gerenciado por deploy/aws/install.sh do IguatamaOnline
[iguatamaonline]
user = www-data
group = www-data
listen = /run/php/php8.3-fpm-iguatamaonline.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm = ondemand
pm.max_children = 5
pm.process_idle_timeout = 10s
pm.max_requests = 500
clear_env = yes
env[IGUA_DEPLOY_VPS] = 1
; IndexNow virtual deve ser habilitado separadamente após configurar o snippet Nginx.
env[IGUA_INDEXNOW_VIRTUAL] = 0
php_admin_flag[display_errors] = off
php_admin_flag[log_errors] = on
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 60
php_admin_value[upload_max_filesize] = 24M
php_admin_value[post_max_size] = 32M
request_terminate_timeout = 90s
POOL
chmod 0644 "$FPM_POOL"
"/usr/sbin/php-fpm$PHP_VERSION" -t >/dev/null
systemctl restart "php$PHP_VERSION-fpm"
# Modifica APENAS o único upstream permitido do vhost exclusivo do portal.
python3 "$SITE/deploy/aws/nginx_upstream.py" \
  --config "$NGINX_CONF" --site-public "$SITE/public" --php-version "$PHP_VERSION" --apply
nginx -t >/dev/null
systemctl reload nginx

# Instalar units do fonte completo, sem permitir que o serviço execute como root.
install -m 0644 "$SITE/deploy/aws/iguatama-deploy.service" /etc/systemd/system/iguatama-deploy.service
install -m 0644 "$SITE/deploy/aws/iguatama-deploy.timer" /etc/systemd/system/iguatama-deploy.timer
systemd-analyze verify /etc/systemd/system/iguatama-deploy.service /etc/systemd/system/iguatama-deploy.timer
systemctl daemon-reload
# `runuser -u` assegura grupos atualizados imediatamente sem exigir logout/login.
runuser -u "$DEPLOY_USER" -- env IGUA_DEPLOY_VPS=1 /usr/bin/php"$PHP_VERSION" "$SITE/bin/aws-deploy-check.php"
for path in "$SITE" "$SITE/app" "$SITE/public" "$SITE/VERSION" "$SITE/storage/updater/vps/private"; do
  if runuser -u "$WEB_USER" -- test -w "$path"; then die "Usuário web pode modificar área protegida: $path"; fi
done
runuser -u "$WEB_USER" -- test -w "$SITE/storage/updater/vps" || die 'Usuário web não consegue enviar pedidos para a fila.'
if [[ -d "$SITE/public/uploads" ]]; then
  runuser -u "$WEB_USER" -- test -w "$SITE/public/uploads" || die 'Uploads não estão graváveis pelo PHP-FPM; corrija sem tocar em arquivos de código.'
fi
systemctl start iguatama-deploy.service
runuser -u "$WEB_USER" -- /usr/bin/php"$PHP_VERSION" -r '
$path=$argv[1];$raw=@file_get_contents($path);$data=$raw?json_decode($raw,true):null;
exit(is_array($data)&&($data["ready"]??false)===true&&($data["at"]??0)>time()-180 ? 0:1);
' "$SITE/storage/updater/vps/worker.json" || die 'Worker não publicou heartbeat válido. Consulte journalctl.'
systemctl enable --now iguatama-deploy.timer
COMPLETED=1
log 'Preparação aprovada. Timer ativo e PHP-FPM web isolado do código.'
log 'Reexecução segura: sudo bash deploy/aws/install.sh (com novo backup confirmado).'
log 'Atenção: banco, DNS, TLS e migração de dados NÃO foram alterados.'
