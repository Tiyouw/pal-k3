#!/usr/bin/env python3
"""Selidiki 3 hal:

1. /laporan/pms, /laporan/temuan, /laporan/kartu/1 balas 200 tapi teks body
   kosong. Bug render, atau halaman khusus cetak yang isinya disembunyikan
   CSS @media print? Dibedakan dengan mengukur panjang HTML mentah dan
   memeriksa gaya yang berlaku.
2. Apakah pemantau (peran baca) benar-benar bisa MENYIMPAN perubahan aset,
   bukan cuma melihat tombol Ubah. Form dibuka saja, TIDAK disimpan.
3. Halaman 403/404 -- apa isinya sekadar teks gundul tanpa jalan pulang.
"""
import json
import sys

from playwright.sync_api import sync_playwright

sys.path.insert(0, "/workspace/qa-pal-k3")
from harness import (BASIS, MEJA, buat_konteks, buka, catatan, masuk,
                     pasang_konsol, rekam)

hasil = {}


def jalan():
    with sync_playwright() as pw:
        br, ctx = buat_konteks(pw, MEJA)
        hal = ctx.new_page()
        galat = pasang_konsol(hal)
        masuk(hal, galat, "admin", "cek-00")

        # 1. halaman yang tampak kosong
        for jalur in ["/laporan/pms", "/laporan/temuan", "/laporan/kartu/1"]:
            hal.goto(BASIS + jalur, wait_until="networkidle", timeout=45000)
            hal.wait_for_timeout(1500)
            info = hal.evaluate("""() => {
              const b = document.body;
              const g = getComputedStyle(b);
              return {
                panjang_html: document.documentElement.outerHTML.length,
                panjang_teks_body: (b.innerText || '').trim().length,
                anak_body: b.children.length,
                display_body: g.display,
                visibility_body: g.visibility,
                opacity_body: g.opacity,
                tinggi_body: b.scrollHeight,
                jml_style: document.querySelectorAll('style,link[rel=stylesheet]').length,
                ada_media_print: Array.from(document.querySelectorAll('style'))
                  .some(s => (s.textContent||'').includes('@media print')),
                teks_awal: (document.documentElement.outerHTML || '')
                  .replace(/\\s+/g,' ').slice(0, 600),
              };
            }""")
            # paksa emulasi media cetak: kalau isinya muncul, ini halaman cetak
            hal.emulate_media(media="print")
            hal.wait_for_timeout(600)
            teks_cetak = hal.evaluate(
                "() => (document.body.innerText||'').trim().slice(0,600)")
            hal.emulate_media(media="screen")
            info["teks_saat_media_print"] = teks_cetak
            hasil[jalur] = info
            nama = "cek-" + jalur.strip("/").replace("/", "-")
            rekam(hal, nama, galat, catat="selidik halaman tampak kosong")
            print(f"{jalur}: html={info['panjang_html']}B "
                  f"teks_layar={info['panjang_teks_body']} "
                  f"teks_cetak={len(teks_cetak)} "
                  f"tinggi={info['tinggi_body']} "
                  f"media_print={info['ada_media_print']}", flush=True)

        ctx.close()
        br.close()

        # 2. pemantau: bisa buka form edit aset? (GET saja, tidak menyimpan)
        br, ctx = buat_konteks(pw, MEJA)
        hal = ctx.new_page()
        galat = pasang_konsol(hal)
        masuk(hal, galat, "pemantau", "cek-pmt-00")
        r = hal.goto(BASIS + "/admin/assets/1/edit",
                     wait_until="domcontentloaded", timeout=45000)
        hal.wait_for_timeout(1500)
        info = hal.evaluate("""() => ({
          jml_input: document.querySelectorAll('input,select,textarea').length,
          ada_tombol_simpan: Array.from(document.querySelectorAll('button'))
            .some(b => /simpan|save/i.test(b.innerText||'')),
          teks_tombol: Array.from(document.querySelectorAll('button'))
            .map(b => (b.innerText||'').trim()).filter(Boolean).slice(0,15),
        })""")
        info["status"] = r.status if r else None
        hasil["pemantau_edit_aset"] = info
        rekam(hal, "cek-pmt-edit-aset", galat,
              catat="pemantau buka form edit aset -- TIDAK disimpan")
        print(f"pemantau /admin/assets/1/edit: status={info['status']} "
              f"input={info['jml_input']} simpan={info['ada_tombol_simpan']}",
              flush=True)
        ctx.close()
        br.close()

    with open("/workspace/qa-pal-k3/hasil_cek_kosong.json", "w") as f:
        json.dump({"selidik": hasil, "tangkapan": catatan}, f,
                  ensure_ascii=False, indent=1)
    print("\nselesai")


if __name__ == "__main__":
    jalan()
