#!/bin/sh
# Inicie SOMENTE no armazenamento privado Termux, nunca expondo -S na rede.
set -eu
case "${PREFIX:-}" in
  /data/data/com.termux/files/usr) ;;
  *) echo 'Este comando é exclusivo do Termux. Em hospedagem, configure DocumentRoot = public/.' >&2; exit 1 ;;
esac
ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd -P)
cd "$ROOT"
PORT=${1:-8000}
case "$PORT" in ''|*[!0-9]*) echo 'A porta deve ser numérica.' >&2; exit 1;; esac
if [ "$PORT" -lt 1 ] || [ "$PORT" -gt 65535 ]; then
  echo 'Porta fora do intervalo 1–65535.' >&2;exit 1
fi
command -v php >/dev/null 2>&1 || { echo 'Instale PHP: pkg install php' >&2; exit 1; }
# Verifica a extensão mais importante antes de abrir o servidor.
if ! php -r 'exit(extension_loaded("pdo_mysql") ? 0 : 1);'; then
  echo 'O PHP do Termux não possui pdo_mysql. Confira php -m e instale/habilite o driver antes de iniciar.' >&2
  exit 1
fi
umask 077
mkdir -p storage/backups storage/media storage/logs storage/security/login public/uploads
echo "Abrir no mesmo aparelho: http://127.0.0.1:$PORT/setup.php (primeira instalação)"
echo 'Limites de importação desta execução: upload_max_filesize=20M; post_max_size=24M.'
echo 'Se o instalador ainda indicar 2M/8M, feche o servidor antigo e use esta porta.'
echo 'Pare com Ctrl+C. Inicie MariaDB separadamente antes do instalador.'
exec php -d upload_max_filesize=20M -d post_max_size=24M -S "127.0.0.1:$PORT" -t public public/router.php
