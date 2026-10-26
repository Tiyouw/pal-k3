#!/usr/bin/env bash
# Deploy versi yang sudah diuji dan aset Vite yang dibangun dari versi sama.
# Pemakaian: bash deploy_demo.sh <commit-40-hex> <build.tar.gz>
set -Eeuo pipefail

RELEASE="${1:?Commit rilis wajib diisi}"
BUILD_ARCHIVE="${2:?Arsip public/build wajib diisi}"
[[ "$RELEASE" =~ ^[0-9a-f]{40}$ ]] || { echo 'Commit rilis tidak sah.' >&2; exit 1; }
[[ -f "$BUILD_ARCHIVE" ]] || { echo 'Arsip build tidak ditemukan.' >&2; exit 1; }

cd "${PAL_APP_DIR:-/home/ubuntu/pal-k3}"
[[ -z "$(git status --porcelain)" ]] || { echo 'Working tree server berisi perubahan; deployment dihentikan.' >&2; exit 1; }
git -c remote.origin.url=https://github.com/Tiyouw/pal-k3.git fetch --quiet origin main
[[ "$(git rev-parse origin/main)" == "$RELEASE" ]] || { echo 'Versi main tidak sama dengan rilis yang diuji.' >&2; exit 1; }
git merge-base --is-ancestor HEAD "$RELEASE"

BACKUP_DIR="$HOME/.pal-k3-backups/$(date -u +%Y%m%dT%H%M%SZ)-${RELEASE:0:8}"
mkdir -p "$BACKUP_DIR"
chmod 700 "$HOME/.pal-k3-backups" "$BACKUP_DIR"
git rev-parse HEAD > "$BACKUP_DIR/commit-sebelumnya.txt"
cp .env "$BACKUP_DIR/.env"
chmod 600 "$BACKUP_DIR/.env"
python3 - "$BACKUP_DIR/database.sqlite" <<'PY'
import sqlite3
import sys
with sqlite3.connect('database/database.sqlite') as source:
    with sqlite3.connect(sys.argv[1]) as destination:
        source.backup(destination)
PY
tar -czf "$BACKUP_DIR/foto.tar.gz" -C storage/app public
tar -czf "$BACKUP_DIR/build.tar.gz" -C public build
echo "Backup tersimpan: $BACKUP_DIR"

on_error() {
    echo "Deployment gagal. Backup: $BACKUP_DIR" >&2
    php artisan up --no-interaction || true
}
trap on_error ERR
php artisan down --retry=15 --no-interaction
git merge --ff-only "$RELEASE"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

python3 <<'PY'
from pathlib import Path
path = Path('.env')
values = {
    'DEMO_MODE': 'true',
    'APP_DEBUG': 'false',
    'APP_URL': 'https://rifaldy.tiyoouw.app',
    'SESSION_SECURE_COOKIE': 'true',
}
lines = path.read_text().splitlines()
for key, value in values.items():
    if any(line.startswith(key + '=') for line in lines):
        lines = [key + '=' + value if line.startswith(key + '=') else line for line in lines]
    else:
        lines.append(key + '=' + value)
path.write_text('\n'.join(lines) + '\n')
PY
php artisan optimize:clear --no-interaction
php artisan migrate --force --no-interaction
php artisan db:seed --class=DemoUserSeeder --force --no-interaction
if [[ ! -e public/storage && ! -L public/storage ]]; then
    php artisan storage:link --no-interaction
fi
tar -xzf "$BUILD_ARCHIVE" -C public
php artisan optimize --no-interaction

# Konfigurasi unggahan berlaku pada proses aplikasi ini dan anak prosesnya.
mkdir -p "$HOME/.config/pal-k3/php"
printf 'upload_max_filesize=12M\npost_max_size=16M\n' > "$HOME/.config/pal-k3/php/99-uploads.ini"
sudo -n mkdir -p /etc/systemd/system/pal-k3.service.d
printf '[Service]\nEnvironment="PHP_INI_SCAN_DIR=/etc/php/8.3/cli/conf.d:%s/.config/pal-k3/php"\n' "$HOME" \
    | sudo -n tee /etc/systemd/system/pal-k3.service.d/uploads.conf > /dev/null
sudo -n systemctl daemon-reload
php artisan up --no-interaction
sudo -n systemctl restart pal-k3.service
curl --fail --silent --retry 5 --retry-connrefused --retry-delay 1 http://127.0.0.1:8001/ > /dev/null
sudo -n systemctl is-active pal-k3.service
echo "DEPLOYED $RELEASE"
echo "BACKUP $BACKUP_DIR"
