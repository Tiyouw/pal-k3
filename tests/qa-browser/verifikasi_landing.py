#!/usr/bin/env python3
"""Tes regresi perilaku JS halaman depan (layouts/situs + resources/js/app.js).

PHPUnit hanya melihat HTML mentah; semua yang di bawah ini baru terjadi di
peramban, jadi hanya bisa dibuktikan lewat Chromium sungguhan:

1. Gerbang kelas `js`: bila app.js gagal dimuat, kelas dicabut setelah ±3 detik
   dan seluruh isi [data-muncul] tetap tampil.
2. Menu ponsel: buka/tutup, Escape mengembalikan fokus ke tombol, panel bisa
   digulir sendiri di landscape, dan cincin fokus TIDAK terpotong tepi panel
   (panel memakai overflow-y:auto, yang ikut memotong outline).
3. Tombol jeda video (WCAG 2.2.2): klik, Spasi, Enter mengganti video.paused.
4. prefers-reduced-motion: video tidak dimuat, tombol jeda tersembunyi.
5. Tak ada gulir horizontal di 360 px dan tak ada galat konsol.

Halaman publik, jadi tidak perlu login. Sasaran bisa diganti lewat QA_BASIS
(mis. preview PR). Keluar dengan kode bukan nol bila ada yang gagal.

    env -u LD_LIBRARY_PATH python3 tests/qa-browser/verifikasi_landing.py
"""
import os
import sys

from playwright.sync_api import sync_playwright

import harness as H

BASIS = os.environ.get("QA_BASIS", H.BASIS).rstrip("/")
hasil = []


def cek(nama, lulus, rinci=""):
    hasil.append((nama, lulus, rinci))
    print(("  LULUS " if lulus else "  GAGAL ") + nama
          + (f"  ({rinci})" if rinci else ""), flush=True)


def lewati(nama, alasan):
    hasil.append((nama, None, alasan))
    print(f"  LEWATI {nama}  ({alasan})", flush=True)


def halaman(br, galat=None, **mode):
    ctx = br.new_context(locale="id-ID", timezone_id="Asia/Jakarta", **mode)
    hal = ctx.new_page()
    if galat is not None:
        hal.on("console", lambda m: galat.append(m.text) if m.type == "error" else None)
        hal.on("pageerror", lambda e: galat.append(str(e)))
    return ctx, hal


def buka(hal):
    hal.goto(BASIS + "/", wait_until="load", timeout=45000)
    hal.wait_for_function("document.documentElement.dataset.situsSiap === '1'", timeout=10000)


# Seberapa jauh cincin fokus elemen aktif keluar dari kotak tampak panel
# (padding box, area yang dipotong overflow). Positif = terpotong sekian px.
JS_POTONG_CINCIN = """(panel) => {
    const el = document.activeElement;
    const g = getComputedStyle(el);
    const lebar = (parseFloat(g.outlineWidth) || 0) + (parseFloat(g.outlineOffset) || 0);
    const r = el.getBoundingClientRect();
    const p = panel.getBoundingClientRect();
    const atas = p.top + panel.clientTop;
    const bawah = atas + panel.clientHeight;
    const kiri = p.left + panel.clientLeft;
    const kanan = kiri + panel.clientWidth;
    return {
        teks: el.textContent.trim(),
        gaya: g.outlineStyle,
        potong: Math.max(atas - (r.top - lebar), (r.bottom + lebar) - bawah,
                         kiri - (r.left - lebar), (r.right + lebar) - kanan, 0),
    };
}"""


def uji_menu(br, ukuran, nama):
    ctx, hal = halaman(br, viewport=ukuran, is_mobile=True, has_touch=True)
    buka(hal)
    tombol = hal.locator("[data-menu-tombol]")
    panel = hal.locator("#menu-ponsel")

    tombol.focus()
    hal.keyboard.press("Enter")
    terbuka = tombol.get_attribute("aria-expanded") == "true" and panel.is_visible()
    cek(f"{nama}: Enter membuka menu", terbuka)

    # Tab ke setiap elemen dalam panel (fokus keyboard memicu :focus-visible).
    terburuk, isi = 0.0, []
    jumlah = panel.locator("a").count()
    for _ in range(jumlah):
        hal.keyboard.press("Tab")
        hal.wait_for_timeout(120)  # tunggu gulir-otomatis saat fokus
        u = panel.evaluate(JS_POTONG_CINCIN)
        isi.append(f"{u['teks']}={u['potong']:.0f}")
        if u["gaya"] == "none":
            terburuk = max(terburuk, 99)
        terburuk = max(terburuk, u["potong"])
    cek(f"{nama}: cincin fokus utuh di {jumlah} tautan panel", terburuk < 0.5,
        "terpotong px: " + ", ".join(isi))

    dapat_gulir = panel.evaluate("p => p.scrollHeight > p.clientHeight")
    if ukuran["height"] < 500:
        cek(f"{nama}: panel menu bisa digulir", dapat_gulir)
        akhir = panel.locator("a").last
        akhir.scroll_into_view_if_needed()
        cek(f"{nama}: tombol masuk di dasar panel terjangkau", akhir.is_visible()
            and akhir.bounding_box()["y"] + akhir.bounding_box()["height"] <= ukuran["height"])

    hal.keyboard.press("Escape")
    kembali = hal.evaluate("document.activeElement.hasAttribute('data-menu-tombol')")
    cek(f"{nama}: Escape menutup menu dan fokus kembali ke tombol",
        panel.is_hidden() and tombol.get_attribute("aria-expanded") == "false" and kembali)
    ctx.close()


def uji_video(br):
    ctx, hal = halaman(br, viewport={"width": 1440, "height": 900})
    buka(hal)
    tombol = hal.locator("[data-video-kendali]")
    try:
        tombol.wait_for(state="visible", timeout=12000)
    except Exception:
        dukung = hal.evaluate("document.createElement('video').canPlayType('video/mp4; codecs=\"avc1.42E01E\"')")
        lewati("video: tombol jeda", f"video tidak berputar di Chromium ini (canPlayType mp4={dukung!r})")
        ctx.close()
        return

    # textContent, bukan inner_text: innerText ikut text-transform:uppercase
    # tombol, jadi yang terbaca "PUTAR VIDEO".
    jeda = lambda: hal.evaluate("document.querySelector('[data-video-latar]').paused")
    label = lambda: (tombol.text_content() or "").strip()
    tombol.click()
    cek("video: klik menjeda", jeda() and label() == "Putar video", label())
    tombol.focus()
    hal.keyboard.press("Space")
    hal.wait_for_timeout(300)
    cek("video: Spasi memutar lagi", not jeda() and label() == "Jeda video", label())
    hal.keyboard.press("Enter")
    hal.wait_for_timeout(300)
    cek("video: Enter menjeda", jeda() and label() == "Putar video", label())

    # Jeda pengguna tidak boleh dibatalkan saat hero digulir keluar lalu kembali.
    hal.evaluate("window.scrollTo(0, document.body.scrollHeight)")
    hal.wait_for_timeout(500)
    hal.evaluate("window.scrollTo(0, 0)")
    hal.wait_for_timeout(800)
    cek("video: jeda pengguna bertahan setelah gulir", jeda())
    ctx.close()


def uji_gerak_dikurangi(br):
    ctx, hal = halaman(br, viewport={"width": 390, "height": 844}, reduced_motion="reduce")
    buka(hal)
    hal.wait_for_timeout(1500)
    src = hal.evaluate("document.querySelector('[data-video-latar]').getAttribute('src')")
    sembunyi = hal.locator("[data-video-kendali]").is_hidden()
    belum = hal.evaluate("[...document.querySelectorAll('[data-muncul]')].filter(e => !e.classList.contains('muncul')).length")
    cek("reduced-motion: video tidak dimuat, tombol jeda tersembunyi", not src and sembunyi, f"src={src!r}")
    cek("reduced-motion: semua reveal langsung tampil", belum == 0, f"{belum} belum tampil")
    ctx.close()


def uji_tanpa_js(br):
    ctx, hal = halaman(br, viewport={"width": 390, "height": 844})
    hal.route("**/build/assets/*.js", lambda r: r.abort())
    hal.goto(BASIS + "/", wait_until="load", timeout=45000)
    hal.wait_for_timeout(3600)
    js = hal.evaluate("document.documentElement.classList.contains('js')")
    tersembunyi = hal.evaluate("""[...document.querySelectorAll('[data-muncul]')]
        .filter(e => parseFloat(getComputedStyle(e).opacity) < 0.99).length""")
    cek("app.js diblokir: kelas js dicabut setelah ±3 detik", not js)
    cek("app.js diblokir: semua isi tetap terlihat", tersembunyi == 0, f"{tersembunyi} tersembunyi")
    ctx.close()


def uji_umum(br):
    galat = []
    ctx, hal = halaman(br, galat, viewport={"width": 360, "height": 740}, is_mobile=True, has_touch=True)
    buka(hal)
    hal.evaluate("window.scrollTo(0, document.body.scrollHeight)")
    hal.wait_for_timeout(800)
    lebar = hal.evaluate("[document.documentElement.scrollWidth, document.documentElement.clientWidth]")
    cek("360 px: tak ada gulir horizontal", lebar[0] <= lebar[1], f"scrollWidth={lebar[0]} clientWidth={lebar[1]}")
    hal.locator("[data-menu-tombol]").click()
    lebar = hal.evaluate("[document.documentElement.scrollWidth, document.documentElement.clientWidth]")
    cek("360 px: tak ada gulir horizontal saat menu terbuka", lebar[0] <= lebar[1])
    cek("tak ada galat konsol", not galat, "; ".join(galat)[:300])
    ctx.close()


def jalan():
    print(f"Sasaran: {BASIS}", flush=True)
    with sync_playwright() as pw:
        br, ctx0 = H.buat_konteks(pw, H.MEJA)
        ctx0.close()
        uji_menu(br, {"width": 390, "height": 844}, "potret 390x844")
        uji_menu(br, {"width": 844, "height": 390}, "landscape 844x390")
        uji_video(br)
        uji_gerak_dikurangi(br)
        uji_tanpa_js(br)
        uji_umum(br)
        br.close()

    gagal = [h for h in hasil if h[1] is False]
    lulus = sum(1 for h in hasil if h[1])
    print(f"\nRINGKAS: {lulus} lulus, {len(gagal)} gagal, "
          f"{sum(1 for h in hasil if h[1] is None)} dilewati", flush=True)
    sys.exit(1 if gagal else 0)


if __name__ == "__main__":
    jalan()
