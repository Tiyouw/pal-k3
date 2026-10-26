#!/usr/bin/env python3
"""Verifikasi peramban untuk redesain panel admin dan login.

Yang diperiksa (semuanya hanya MEMBACA; yang berubah cuma localStorage peramban):
  - login admin dan login petugas: kartu di tengah, ada jarak ke tepi, tanpa gulir
    horizontal, muat satu layar di 1366x768, di 10 ukuran layar;
  - sidebar: ikon pada kunjungan pertama, Ctrl+B, tombol ciutkan di dasar, status
    diingat, kaki sidebar terlihat tanpa gulir di tinggi 560..1080 px, laci ponsel
    tidak menimpa pilihan desktop;
  - kartu area/peta: sakelar berikon SVG, titik, zona, keyboard, diingat;
  - pencarian: Ctrl+K, hasil, panah, wildcard tidak berlaku, token QR tidak muncul;
  - pemantau: ringkasan manajemen, tanpa aksi tulis, tanpa Data Induk.

Akun dibaca seperti harness lain (lihat README): variabel QA_ADMIN dan QA_PEMANTAU
berformat nip:sandi, atau berkas rahasia di luar repo. Jangan menaruh sandi di sini.

Lingkungan:
  QA_BASIS      alamat aplikasi (bawaan http://127.0.0.1:8000)
  QA_CHROMIUM   jalur peramban Chromium (opsional; bawaan: milik Playwright)
  QA_TANGKAPAN  folder tangkapan layar (opsional)

Keluar dengan kode bukan-nol kalau ada pemeriksaan yang gagal.
"""
import os
import re
import sys

from playwright.sync_api import sync_playwright

BASIS = os.environ.get("QA_BASIS", "http://127.0.0.1:8000").rstrip("/")
CHROMIUM = os.environ.get("QA_CHROMIUM") or None
TANGKAPAN = os.environ.get("QA_TANGKAPAN")
UKURAN_LOGIN = [(1920, 1080), (1440, 900), (1366, 768), (1024, 768), (900, 700),
                (820, 1180), (700, 900), (390, 844), (360, 640), (844, 390)]
MUAT_SATU_LAYAR = {(1920, 1080), (1440, 900), (1366, 768)}

hasil = []


def rahasia():
    data = {}
    jalur = os.environ.get("QA_PAL_RAHASIA", os.path.expanduser("~/.pal-k3-qa.env"))
    if os.path.exists(jalur):
        for baris in open(jalur, encoding="utf-8"):
            baris = baris.strip()
            if baris and not baris.startswith("#") and "=" in baris:
                k, v = baris.split("=", 1)
                data[k.strip()] = v.strip()
    for k in ("QA_ADMIN", "QA_PEMANTAU"):
        if os.environ.get(k):
            data[k] = os.environ[k]
    return data


def akun(peran):
    nilai = rahasia().get(peran)
    if not nilai or ":" not in nilai:
        sys.exit(f"{peran} belum diisi (format nip:sandi). Lihat README harness.")
    return nilai.split(":", 1)


def cek(nama, kondisi, info=""):
    hasil.append((nama, bool(kondisi)))
    print("OK    " if kondisi else "GAGAL ", nama, f"| {info}" if info and not kondisi else "")


def tangkap(pg, nama, **kw):
    if TANGKAPAN:
        os.makedirs(TANGKAPAN, exist_ok=True)
        pg.screenshot(path=os.path.join(TANGKAPAN, nama), **kw)


def masuk(pg, peran):
    nip, sandi = akun(peran)
    pg.goto(BASIS + "/admin/login", wait_until="networkidle")
    pg.fill('input[id="data.nip"]', nip)
    pg.fill('input[id="data.password"]', sandi)
    pg.click("button[type=submit]")
    pg.wait_for_url(re.compile(r"/admin/?$"), timeout=20000)
    pg.wait_for_load_state("networkidle")


def lebar_sidebar(pg):
    return pg.evaluate("() => Math.round(document.querySelector('.fi-sidebar').getBoundingClientRect().width)")


def kaki_terlihat(pg):
    return pg.evaluate("""() => { const v = innerHeight;
      const a = document.querySelector('.k3-ciut').getBoundingClientRect(), u = document.querySelector('.k3-sidebar-user').getBoundingClientRect();
      return {ciut: [Math.round(a.top), Math.round(a.bottom)], user: [Math.round(u.top), Math.round(u.bottom)], v,
              ok: a.top >= 0 && a.bottom <= v + 1 && u.top >= 0 && u.bottom <= v + 1} }""")


def terlihat(pg, selektor, harus=True, batas=4000):
    """Menunggu (polling) sampai elemen terlihat/tersembunyi. Setelah klik, Alpine dan pembaruan
    Livewire butuh beberapa milidetik; membaca DOM seketika memberi hasil yang kebetulan."""
    waktu = 0
    while waktu <= batas:
        if pg.locator(selektor).first.is_visible() == harus:
            return True
        pg.wait_for_timeout(100)
        waktu += 100
    return False


def teks_hasil(pg):
    loc = pg.locator(".fi-global-search-results-ctn")
    return loc.inner_text() if loc.count() else ""


def cari(pg, teks):
    """Tunggu respons pencarian yang benar, termasuk debounce dan render Livewire."""
    def respons_pencarian(r):
        if "/livewire/update" not in r.url or r.request.method != "POST":
            return False
        payload = r.request.post_data_json or {}
        return any(c.get("updates", {}).get("search") == teks for c in payload.get("components", []))

    with pg.expect_response(respons_pencarian, timeout=20000) as respons:
        pg.fill("input[type=search]", teks)
    respons.value.body()
    pg.wait_for_load_state("networkidle")


def uji_login(b):
    js = """() => { const c = document.querySelector('.k3-login-shell').getBoundingClientRect(), de = document.documentElement;
      const tinggi = Math.max(de.scrollHeight, document.body.scrollHeight);
      return {w: Math.round(c.width), h: Math.round(c.height), l: Math.round(c.left), r: Math.round(de.clientWidth - c.right),
              t: Math.round(c.top + scrollY), b: Math.round(tinggi - (c.bottom + scrollY)),
              gulirV: tinggi > innerHeight + 1, gulirH: de.scrollWidth > de.clientWidth + 1} }"""
    for nama, jalur in (("admin", "/admin/login"), ("petugas", "/masuk")):
        for (w, h) in UKURAN_LOGIN:
            pg = b.new_page(viewport={"width": w, "height": h})
            galat = []
            pg.on("pageerror", lambda e: galat.append(str(e)))
            pg.goto(BASIS + jalur, wait_until="networkidle")
            m = pg.evaluate(js)
            tengah = abs(m["l"] - m["r"]) <= 1 and abs(m["t"] - m["b"]) <= 1
            ok = tengah and min(m["l"], m["r"], m["t"], m["b"]) >= 16 and not m["gulirH"] and not galat
            if (w, h) in MUAT_SATU_LAYAR:
                ok = ok and not m["gulirV"]
            cek(f"login {nama} {w}x{h}: di tengah, ada jarak, tanpa gulir mendatar", ok, (m, galat))
            cek(f"login {nama} {w}x{h}: merek Geofencing", pg.locator(".k3-login-story .k3-merek-teks b").inner_text() == "Geofencing"
                if pg.locator(".k3-login-story .k3-merek-teks b").count() else False)
            if (w, h) in ((1366, 768), (390, 844)):
                tangkap(pg, f"login-{nama}-{w}x{h}.png")
            pg.close()


def uji_admin(b):
    ctx = b.new_context(viewport={"width": 1440, "height": 900})
    pg = ctx.new_page()
    galat = []
    pg.on("pageerror", lambda e: galat.append(str(e)))
    pg.on("console", lambda m: galat.append("console: " + m.text) if m.type == "error" and "fonts.g" not in m.text else None)
    masuk(pg, "QA_ADMIN")

    cek("sidebar: kunjungan pertama = ikon", lebar_sidebar(pg) <= 80, lebar_sidebar(pg))
    cek("sidebar: <html> tidak berubah jadi flex (bentrok nama kelas)", pg.evaluate("() => getComputedStyle(document.documentElement).display") != "flex")
    cek("sidebar: tanda Geofencing terlihat saat ciut", pg.evaluate("() => { const e = document.querySelector('.fi-sidebar-header .k3-merek-tanda'); return !!e && e.getBoundingClientRect().width > 20 }"))
    cek("sidebar: wordmark tersembunyi saat ciut", pg.evaluate("() => getComputedStyle(document.querySelector('.fi-sidebar-header .k3-merek-teks')).display") == "none")
    cek("sidebar: chevron bawaan Filament tersembunyi", pg.evaluate("() => [...document.querySelectorAll('.fi-sidebar-header .fi-icon-btn')].every(e => getComputedStyle(e).display === 'none')"))
    kt = kaki_terlihat(pg)
    cek("sidebar: kaki terlihat tanpa gulir (ikon)", kt["ok"], kt)
    tangkap(pg, "admin-ikon-1440.png")

    pg.keyboard.press("Control+b"); pg.wait_for_timeout(500)
    cek("Ctrl+B membuka sidebar penuh", lebar_sidebar(pg) >= 250, lebar_sidebar(pg))
    cek("pilihan desktop tersimpan", pg.evaluate("() => localStorage.getItem('k3SidebarDesktop')") == "terbuka")
    cek("wordmark Geofencing terlihat saat terbuka", pg.locator(".fi-sidebar-header .k3-merek-teks b").is_visible())
    cek("nama dan peran pengguna terlihat saat terbuka", pg.locator(".k3-sidebar-user .k3-user-name").is_visible() and pg.locator(".k3-user-role").is_visible())
    cek("hanya satu tombol ciutkan terlihat", pg.locator(".k3-ciut:visible").count() == 1)
    tangkap(pg, "admin-terbuka-1440.png")
    pg.reload(wait_until="networkidle")
    cek("muat ulang: tetap terbuka", lebar_sidebar(pg) >= 250)
    pg.click(".k3-ciut"); pg.wait_for_timeout(500)
    cek("tombol di dasar menciutkan", lebar_sidebar(pg) <= 80)
    pg.reload(wait_until="networkidle")
    cek("muat ulang: tetap ikon", lebar_sidebar(pg) <= 80)

    for tinggi in (560, 640, 720, 768, 1080):
        pg.set_viewport_size({"width": 1440, "height": tinggi}); pg.wait_for_timeout(200)
        if lebar_sidebar(pg) <= 80:
            pg.keyboard.press("Control+b"); pg.wait_for_timeout(450)
        kt = kaki_terlihat(pg)
        cek(f"sidebar terbuka tinggi {tinggi}: kaki terlihat tanpa gulir", kt["ok"], kt)
        pg.keyboard.press("Control+b"); pg.wait_for_timeout(450)
    pg.set_viewport_size({"width": 1440, "height": 900})

    pg.reload(wait_until="networkidle")
    cek("kartu: tampilan awal = titik", terlihat(pg, ".k3-area-grid") and terlihat(pg, ".k3-peta-kanvas", harus=False))
    cek("sakelar: dua tombol berikon SVG tanpa teks", pg.locator(".k3-sakelar-btn svg").count() == 2 and pg.locator(".k3-sakelar-btn").first.inner_text().strip() == "")
    cek("sakelar: label aksesibel dan tepat satu aria-pressed=true", pg.locator('.k3-sakelar-btn[aria-label="Tampilan peta"]').count() == 1 and pg.locator('.k3-sakelar-btn[aria-pressed="true"]').count() == 1)
    pg.click('.k3-sakelar-btn[aria-label="Tampilan peta"]'); pg.wait_for_timeout(300)
    cek("peta tampil setelah sakelar", terlihat(pg, ".k3-peta-kanvas") and terlihat(pg, ".k3-area-grid", harus=False))
    titik = pg.locator(".k3-peta-svg:visible .k3-ttk").count()
    cek("peta Semua memuat titik", titik > 0, titik)
    cek("HTML dasbor tidak memuat token QR", pg.evaluate("() => !document.documentElement.innerHTML.includes('PAL-K3-')"))
    pg.locator(".k3-peta-svg:visible .k3-ttk").first.click()
    cek("klik titik: info unit tampil", "Peta skematik" not in pg.locator(".k3-peta-info").inner_text())
    if titik > 1:
        pg.keyboard.press("Tab"); pg.keyboard.press("Enter")
        cek("keyboard: Tab+Enter memilih titik berikutnya", pg.locator(".k3-peta-svg:visible .k3-ttk.is-pilih").count() == 1)
    chip = pg.locator(".k3-zona button")
    if chip.count() > 2:
        chip.nth(1).click(); pg.wait_for_timeout(300)
        cek("chip zona memperbesar ke satu area", 0 < pg.locator(".k3-peta-svg:visible .k3-ttk").count() < titik)
        tangkap(pg, "admin-peta-zona-1440.png")
        chip.nth(0).click()
    pg.reload(wait_until="networkidle")
    cek("pilihan peta diingat", terlihat(pg, ".k3-peta-kanvas"))
    tangkap(pg, "admin-peta-1440.png")
    kpi = pg.locator("button.k3-pick")
    if kpi.count():
        kpi.nth(1).click(); pg.wait_for_timeout(800)
        pg.wait_for_load_state("networkidle")
        cek("mode peta bertahan setelah pembaruan Livewire (ganti tab antrean)", terlihat(pg, ".k3-peta-kanvas"))
    pg.click('.k3-sakelar-btn[aria-label="Tampilan titik per area"]')
    cek("sakelar kembali ke titik", terlihat(pg, ".k3-area-grid") and terlihat(pg, ".k3-peta-kanvas", harus=False))

    pg.keyboard.press("Control+k"); pg.wait_for_timeout(300)
    cek("Ctrl+K memfokuskan kolom cari", pg.evaluate("() => !!document.activeElement && document.activeElement.type === 'search'"))
    cari(pg, "01D")
    t = teks_hasil(pg)
    cek('cari "01D": ada hasil Aset', "ASET" in t.upper() and "01D" in t, t[:160])
    cek("hasil pencarian tidak memuat token QR", "PAL-K3-" not in t)
    tangkap(pg, "admin-cari-1440.png")
    pg.keyboard.press("ArrowDown")
    cek("ArrowDown memindahkan fokus ke hasil", pg.evaluate("() => document.activeElement.classList.contains('fi-global-search-result-link')"))
    pg.keyboard.press("Escape")
    cari(pg, "%%")
    t = teks_hasil(pg)
    cek('cari "%%": wildcard tidak berlaku', "ASET" not in t.upper() and "INSPEKSI" not in t.upper(), t[:120])
    cari(pg, "PAL-K3")
    cek("cari awalan token QR: tidak menemukan aset", "ASET" not in teks_hasil(pg).upper())
    cari(pg, "stiker")
    t = teks_hasil(pg).upper()
    cek('cari "stiker": ada Halaman dan Tindakan untuk admin', "HALAMAN" in t and "TINDAKAN" in t, t[:160])
    cek("admin: tanpa galat JS", not galat, galat)
    pg.keyboard.press("Escape")

    pg.evaluate("() => localStorage.setItem('k3SidebarDesktop', 'terbuka')")
    pg.set_viewport_size({"width": 390, "height": 844})
    pg.reload(wait_until="networkidle")
    cek("ponsel: laci tertutup saat halaman dibuka", pg.evaluate("() => Math.round(document.querySelector('.fi-sidebar').getBoundingClientRect().right)") <= 2)
    pg.click(".fi-topbar-open-sidebar-btn, .fi-topbar button[x-on\\:click*='sidebar']"); pg.wait_for_timeout(500)
    cek("ponsel: tombol menu membuka laci", pg.evaluate("() => Math.round(document.querySelector('.fi-sidebar').getBoundingClientRect().right)") > 200)
    tangkap(pg, "admin-laci-390.png")
    pg.click(".k3-close-menu"); pg.wait_for_timeout(500)
    cek("ponsel: laci menutup", pg.evaluate("() => Math.round(document.querySelector('.fi-sidebar').getBoundingClientRect().right)") <= 2)
    cek("ponsel: pilihan desktop TIDAK tertimpa", pg.evaluate("() => localStorage.getItem('k3SidebarDesktop')") == "terbuka")
    cek("ponsel: halaman tidak melebar", pg.evaluate("() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1"))
    tangkap(pg, "admin-390.png", full_page=True)
    ctx.close()


def uji_pemantau(b):
    ctx = b.new_context(viewport={"width": 1440, "height": 900})
    pg = ctx.new_page()
    galat = []
    pg.on("pageerror", lambda e: galat.append(str(e)))
    masuk(pg, "QA_PEMANTAU")
    isi = pg.locator("main").inner_text()
    cek("pemantau: ringkasan untuk manajemen", "Ringkasan untuk manajemen" in isi and "Risiko terbuka" in isi)
    cek("pemantau: 'Akses hanya baca'", "Akses hanya baca" in isi)
    cek("pemantau: tanpa aksi tulis", not any(x in isi for x in ("Tetapkan tenggat", "Tandai selesai", "Tinjau posisi")))
    cek("pemantau: tanpa KPI dan antrean keputusan", pg.locator(".k3-stats").count() == 0 and pg.locator("#antrean").count() == 0)
    cek("pemantau: tiga tautan laporan", pg.locator(".k3-lap a").count() == 3)
    cek("pemantau: tanpa grup Data Induk", "DATA INDUK" not in pg.locator(".fi-sidebar").inner_text().upper())
    cek("pemantau: sidebar ikon pada kunjungan pertama", lebar_sidebar(pg) <= 80)
    tangkap(pg, "pemantau-1440.png", full_page=True)
    pg.keyboard.press("Control+k"); cari(pg, "stiker")
    t = teks_hasil(pg).upper()
    cek("pemantau: pencarian tanpa Tindakan dan Aset", "TINDAKAN" not in t and "ASET" not in t, t[:160])
    cari(pg, "01D")
    cek("pemantau: aset (Data Induk) tidak muncul di hasil", "ASET" not in teks_hasil(pg).upper())
    pg.keyboard.press("Escape")
    pg.set_viewport_size({"width": 390, "height": 844})
    pg.reload(wait_until="networkidle")
    cek("pemantau ponsel: halaman tidak melebar", pg.evaluate("() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1"))
    tangkap(pg, "pemantau-390.png", full_page=True)
    cek("pemantau: tanpa galat JS", not galat, galat)
    ctx.close()


def main():
    akun("QA_ADMIN"); akun("QA_PEMANTAU")  # gagal cepat kalau akun belum diisi
    with sync_playwright() as p:
        opsi = {"args": ["--no-sandbox"]}
        if CHROMIUM:
            opsi["executable_path"] = CHROMIUM
        b = p.chromium.launch(**opsi)
        uji_login(b)
        uji_admin(b)
        uji_pemantau(b)
        b.close()
    gagal = [n for n, ok in hasil if not ok]
    print(f"\nTOTAL: {len(hasil)} pemeriksaan, GAGAL: {len(gagal)}")
    for n in gagal:
        print("  -", n)
    sys.exit(1 if gagal else 0)


if __name__ == "__main__":
    main()
