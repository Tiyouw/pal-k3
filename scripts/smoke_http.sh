#!/usr/bin/env bash
# Smoke HTTP alur nyata: login sesi, gerbang QR, formulir, laporan.
#
# Dijalankan di peladen (AWS). Dipisah jadi berkas supaya SSH tidak menggantung:
# `php artisan serve &` yang stdout-nya masih menempel ke kanal SSH membuat
# klien menunggu EOF selamanya, jadi peladen dilepas dengan setsid + nohup dan
# seluruh keluarannya dialihkan ke berkas.
set -uo pipefail

PORT="${1:-8130}"
BASE="http://127.0.0.1:${PORT}"
LOG="/tmp/smoke_serve_${PORT}.log"
SANDI="${SANDI:-SANDI-DICABUT-DARI-RIWAYAT}"

cd "$(dirname "$0")/.." || exit 1

pkill -f "artisan serve --port=${PORT}" 2>/dev/null
sleep 1

setsid nohup php artisan serve --port="${PORT}" >"${LOG}" 2>&1 < /dev/null &
SERVER_PID=$!
trap 'kill "${SERVER_PID}" 2>/dev/null; pkill -f "artisan serve --port=${PORT}" 2>/dev/null' EXIT

# Tunggu peladen siap, jangan tidur buta.
for _ in $(seq 1 30); do
    if [ "$(curl -s -o /dev/null -w '%{http_code}' "${BASE}/" 2>/dev/null)" = "200" ]; then
        break
    fi
    sleep 1
done

kode() { curl -s -b "$2" -o /dev/null -w '%{http_code}' "${BASE}$1"; }

csrf() {
    curl -s -c "$1" "${BASE}/masuk" \
        | grep -o 'name="_token" value="[^"]*"' \
        | head -1 | sed 's/.*value="//;s/"//'
}

masuk() { # $1=jar $2=nip
    local jar="$1" nip="$2" token
    rm -f "${jar}"
    token="$(csrf "${jar}")"
    curl -s -b "${jar}" -c "${jar}" -o /dev/null -w '%{redirect_url}' \
        -X POST "${BASE}/masuk" -d "_token=${token}&nip=${nip}&password=${SANDI}"
}

echo "== tamu =="
for p in / /masuk /admin/login; do printf '%-22s %s\n' "$p" "$(curl -s -o /dev/null -w '%{http_code}' "${BASE}$p")"; done

echo "== inspektur (2001) =="
J=/tmp/jar_insp.txt
echo "login -> $(masuk "${J}" 2001)"
for p in /petugas /petugas/pindai /petugas/riwayat /stiker /laporan; do
    printf '%-22s %s\n' "$p" "$(kode "$p" "${J}")"
done

TOKEN="$(php artisan tinker --execute='echo App\Models\Asset::first()->qr_token;' 2>/dev/null | tail -1 | tr -d '\r\n ')"
printf '%-22s %s\n' "/i/{token} (302?)" "$(kode "/i/${TOKEN}" "${J}")"
printf '%-22s %s\n' "/i/{token} diikuti" "$(curl -s -b "${J}" -c "${J}" -o /dev/null -w '%{http_code}' -L "${BASE}/i/${TOKEN}")"
printf '%-22s %s\n' "/i/token-palsu" "$(kode '/i/PAL-K3-0000000000000000000000000000dead' "${J}")"

echo "== pemantau (3001) =="
JP=/tmp/jar_pemantau.txt
echo "login -> $(masuk "${JP}" 3001)"
for p in /stiker /laporan /laporan/pms /laporan/temuan /laporan/kartu/1 /petugas; do
    printf '%-22s %s\n' "$p" "$(kode "$p" "${JP}")"
done

echo "== admin (1001) =="
JA=/tmp/jar_admin.txt
echo "login -> $(masuk "${JA}" 1001)"
for p in /admin/assets /admin/inspections /admin/issues; do
    printf '%-22s %s\n' "$p" "$(kode "$p" "${JA}")"
done
