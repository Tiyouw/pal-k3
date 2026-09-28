#!/usr/bin/env python3
"""Harness QA untuk pal-k3.

Alasan ada berkas ini: subagent sebelumnya menghasilkan 8 PNG dengan hash
identik -- memotret halaman yang sama berulang tanpa sadar. Jadi di sini
setiap tangkapan dicatat hash-nya dan dibandingkan; kalau ada dua langkah
berbeda yang hasilnya identik, itu dilaporkan sebagai CURIGA supaya saya
tidak menarik kesimpulan dari bukti palsu.
"""
import hashlib
import json
import os
import sys
import time

from playwright.sync_api import sync_playwright

BASIS = "https://rifaldy.tiyoouw.app"
CHROME = os.path.expanduser(
    "~/.cache/ms-playwright/chromium-1243/chrome-linux64/chrome"
)

# Container ini tak punya pustaka sistem untuk Chromium dan tak ada root,
# jadi paket .deb sudah diekstrak manual ke DEPS. Jalur ini WAJIB ditanam
# di sini, bukan diandalkan dari env shell: proses latar belakang tidak
# mewarisi LD_LIBRARY_PATH dan Chromium gagal dengan
# "libglib-2.0.so.0: cannot open shared object file".
DEPS = "/workspace/.tools/chromedeps"
_JALUR_LIB = ":".join([
    f"{DEPS}/usr/lib/x86_64-linux-gnu",
    f"{DEPS}/lib/x86_64-linux-gnu",
    f"{DEPS}/usr/lib",
])
if _JALUR_LIB not in os.environ.get("LD_LIBRARY_PATH", ""):
    os.environ["LD_LIBRARY_PATH"] = _JALUR_LIB + ":" + os.environ.get(
        "LD_LIBRARY_PATH", "")
os.environ.setdefault("FONTCONFIG_PATH", "/etc/fonts")
KELUARAN = "/workspace/qa-pal-k3"
SHOTS = f"{KELUARAN}/shots"

# Ponsel: lane inspektur dipakai sambil berdiri di depan tabung, satu tangan.
PONSEL = dict(viewport={"width": 390, "height": 844}, device_scale_factor=2,
              is_mobile=True, has_touch=True,
              user_agent=("Mozilla/5.0 (Linux; Android 13; Pixel 7) "
                          "AppleWebKit/537.36 (KHTML, like Gecko) "
                          "Chrome/153.0.0.0 Mobile Safari/537.36"))
MEJA = dict(viewport={"width": 1440, "height": 900})

# Kredensial dan token dibaca dari berkas rahasia di luar repo.
#
# Alasannya: repo pal-k3 publik. Versi awal berkas ini menanam sandi produksi
# tiga akun dan token QR asli sebagai teks biasa. Kalau ikut ter-commit,
# keduanya terbit permanen di riwayat git, dan memulihkannya berarti git
# filter-repo plus mencetak ulang 35 stiker fisik untuk kedua kalinya.
#
# Isi berkas (satu pasangan per baris, mode 600):
#   QA_ADMIN=1001:sandi
#   QA_INSPEKTUR=2001:sandi
#   QA_PEMANTAU=3001:sandi
#   QA_QR_HIDUP=PAL-K3-...
#   QA_QR_MATI=PAL-K3-...
RAHASIA = os.environ.get(
    "QA_PAL_RAHASIA", os.path.expanduser("~/.pal-k3-qa.env")
)


def _muat_rahasia():
    nilai = {}
    if os.path.exists(RAHASIA):
        for baris in open(RAHASIA):
            baris = baris.strip()
            if not baris or baris.startswith("#") or "=" not in baris:
                continue
            k, v = baris.split("=", 1)
            nilai[k.strip()] = v.strip()
    # env shell menang atas berkas: memudahkan menjalankan sekali pakai
    nilai.update({k: v for k, v in os.environ.items() if k.startswith("QA_")})
    return nilai


_R = _muat_rahasia()


def _akun(kunci):
    mentah = _R.get(kunci)
    if not mentah or ":" not in mentah:
        sys.exit(
            f"Rahasia {kunci} belum ada. Isi {RAHASIA} dengan baris "
            f"{kunci}=nip:sandi (mode 600), atau setel variabel lingkungannya."
        )
    nip, sandi = mentah.split(":", 1)
    return nip, sandi


QR_HIDUP = _R.get("QA_QR_HIDUP", "")
QR_MATI = _R.get("QA_QR_MATI", "")

AKUN = {
    "admin": _akun("QA_ADMIN"),
    "inspektur": _akun("QA_INSPEKTUR"),
    "pemantau": _akun("QA_PEMANTAU"),
}

catatan = []
hash_ke_nama = {}


def rekam(hal, nama, galat_konsol, status=None, catat=""):
    """Simpan tangkapan penuh + hash, deteksi tangkapan kembar."""
    jalur = f"{SHOTS}/{nama}.png"
    hal.screenshot(path=jalur, full_page=True)
    h = hashlib.md5(open(jalur, "rb").read()).hexdigest()
    kembar = hash_ke_nama.get(h)
    if kembar is None:
        hash_ke_nama[h] = nama
    # judul + h1 + teks kasar dipakai untuk verifikasi isi, bukan cuma gambar
    try:
        judul = hal.title()
    except Exception:
        judul = "?"
    try:
        teks = hal.inner_text("body")[:2000]
    except Exception:
        teks = ""
    item = {
        "nama": nama,
        "url": hal.url,
        "status_http": status,
        "judul": judul,
        "md5": h,
        "kembar_dengan": kembar,
        "galat_konsol": galat_konsol[:],
        "cuplikan_teks": " ".join(teks.split())[:900],
        "catat": catat,
    }
    catatan.append(item)
    tanda = f"  KEMBAR<-{kembar}" if kembar else ""
    print(f"[{nama}] {status} {hal.url}{tanda}", flush=True)
    galat_konsol.clear()
    return item


def buat_konteks(pw, mode):
    br = pw.chromium.launch(executable_path=CHROME, args=[
        "--no-sandbox", "--disable-dev-shm-usage", "--disable-gpu",
    ])
    ctx = br.new_context(locale="id-ID", timezone_id="Asia/Jakarta", **mode)
    return br, ctx


def pasang_konsol(hal):
    galat = []
    hal.on("console", lambda m: galat.append(f"{m.type}: {m.text}")
           if m.type in ("error", "warning") else None)
    hal.on("pageerror", lambda e: galat.append(f"pageerror: {e}"))
    return galat


def buka(hal, jalur, galat, nama, catat=""):
    resp = hal.goto(BASIS + jalur, wait_until="domcontentloaded", timeout=45000)
    hal.wait_for_timeout(1200)
    return rekam(hal, nama, galat, resp.status if resp else None, catat)


def masuk(hal, galat, peran, awalan):
    """Login lewat /masuk pakai NIP. Bukan email."""
    nip, sandi = AKUN[peran]
    hal.goto(BASIS + "/masuk", wait_until="domcontentloaded", timeout=45000)
    hal.wait_for_timeout(800)
    rekam(hal, f"{awalan}-form-masuk", galat, catat=f"form login untuk {peran}")
    # isi kolom apa pun namanya: cari input teks pertama + input password
    hal.fill("input[name=nip]", nip)
    hal.fill("input[type=password]", sandi)
    hal.click("button[type=submit]")
    hal.wait_for_load_state("domcontentloaded")
    hal.wait_for_timeout(1500)
    return rekam(hal, f"{awalan}-sesudah-masuk", galat,
                 catat=f"hasil login {peran} nip={nip}")
