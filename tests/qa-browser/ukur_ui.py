#!/usr/bin/env python3
"""Ukur keluhan UI opsi C dengan angka, bukan kesan mata.

Temuan opsi C ditulis dari melihat tangkapan layar. Sebelum mengubah CSS,
setiap keluhan diukur di peramban sungguhan supaya perbaikan bisa dibuktikan
naik-turunnya: rasio kontras WCAG, tinggi kotak sentuh, dan selisih piksel
antara chip terakhir dengan palang bawah yang melayang.

Keluaran: /workspace/qa-pal-k3/ukuran_ui.json
"""
import json

from playwright.sync_api import sync_playwright

import harness as H

# Rumus luminansi relatif WCAG 2.x. Ditulis di JS supaya warna yang dipakai
# adalah warna hasil hitung peramban (termasuk var() dan pewarisan), bukan
# nilai yang saya kira ada di berkas CSS.
JS_UKUR = r"""
() => {
  const lum = (c) => {
    const [r, g, b] = c.map(v => {
      v = v / 255;
      return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
  };
  const rgb = (s) => (s.match(/\d+(\.\d+)?/g) || []).slice(0, 3).map(Number);
  const opaque = (el) => {
    // Warna latar efektif: naik ke induk sampai ketemu yang tidak transparan.
    let n = el;
    while (n) {
      const bg = getComputedStyle(n).backgroundColor;
      const a = bg.match(/rgba?\([^)]*\)/) ? bg : 'rgb(255,255,255)';
      const parts = (a.match(/\d+(\.\d+)?/g) || []).map(Number);
      if (parts.length < 4 || parts[3] > 0.95) return parts.slice(0, 3);
      n = n.parentElement;
    }
    return [255, 255, 255];
  };
  const rasio = (fg, bg) => {
    const a = lum(fg), b = lum(bg);
    const hi = Math.max(a, b), lo = Math.min(a, b);
    return Math.round(((hi + 0.05) / (lo + 0.05)) * 100) / 100;
  };

  const hasil = { chip: [], palang: null, tumpang: null, luber: [] };

  document.querySelectorAll('.lencana').forEach(el => {
    const cs = getComputedStyle(el);
    const r = el.getBoundingClientRect();
    hasil.chip.push({
      kelas: el.className,
      teks: el.innerText.trim().slice(0, 14),
      warna: cs.color,
      latar: cs.backgroundColor,
      rasio: rasio(rgb(cs.color), opaque(el)),
      px: Math.round(parseFloat(cs.fontSize) * 100) / 100,
      tinggi: Math.round(r.height * 100) / 100,
      lebar: Math.round(r.width * 100) / 100,
    });
  });

  const palang = document.querySelector('.palang');
  if (palang) {
    const p = palang.getBoundingClientRect();
    hasil.palang = { atas: Math.round(p.top), tinggi: Math.round(p.height) };
    // Elemen terakhir di aliran isi: apakah tertutup palang saat digulir habis?
    window.scrollTo(0, document.body.scrollHeight);
    const semua = [...document.querySelectorAll('main .lencana, main .tbl, main p, main a')];
    let terburuk = null;
    for (const el of semua) {
      const r = el.getBoundingClientRect();
      if (r.height === 0) continue;
      const tertutup = r.bottom - palang.getBoundingClientRect().top;
      if (tertutup > 0 && (!terburuk || tertutup > terburuk.tertutup_px)) {
        terburuk = {
          teks: el.innerText.trim().slice(0, 30),
          kelas: el.className,
          tertutup_px: Math.round(tertutup),
        };
      }
    }
    hasil.tumpang = terburuk;
  }

  // Luber mendatar: ada isi yang keluar dari lebar layar 390?
  document.querySelectorAll('main *').forEach(el => {
    const r = el.getBoundingClientRect();
    if (r.width > 0 && (r.right > window.innerWidth + 1 || r.left < -1)) {
      hasil.luber.push({
        kelas: el.className || el.tagName,
        kanan: Math.round(r.right),
        layar: window.innerWidth,
      });
    }
  });

  hasil.scroll_maks = document.body.scrollHeight;
  hasil.padding_bawah = getComputedStyle(document.querySelector('.bingkai')).paddingBottom;
  return hasil;
}
"""


def main():
    laporan = {}
    with sync_playwright() as pw:
        br, ctx = H.buat_konteks(pw, H.PONSEL)
        hal = ctx.new_page()
        galat = H.pasang_konsol(hal)
        H.masuk(hal, galat, "inspektur", "ukur-insp")

        for nama, jalur in [("beranda", "/petugas"), ("riwayat", "/petugas/riwayat")]:
            H.buka(hal, jalur, galat, f"ukur-{nama}")
            laporan[nama] = hal.evaluate(JS_UKUR)
            # Aksi kembar: hitung tautan/tombol dengan tujuan sama di satu halaman.
            laporan[nama]["aksi"] = hal.evaluate(r"""
              () => [...document.querySelectorAll('a[href], button')].map(e => ({
                teks: e.innerText.trim().slice(0, 28),
                href: e.getAttribute('href') || '',
                kelas: e.className,
              })).filter(x => x.teks)
            """)
            print(f"--- {nama} ---", flush=True)
            for c in laporan[nama]["chip"]:
                bendera = "GAGAL" if c["rasio"] < 4.5 else "lulus"
                kecil = "  KOTAK<24px" if c["tinggi"] < 24 else ""
                print(f"  [{bendera}] rasio={c['rasio']} px={c['px']} "
                      f"tinggi={c['tinggi']} {c['kelas']} '{c['teks']}'{kecil}",
                      flush=True)
            print(f"  palang: {laporan[nama]['palang']}", flush=True)
            print(f"  tertutup: {laporan[nama]['tumpang']}", flush=True)
            print(f"  luber: {laporan[nama]['luber'][:3]}", flush=True)

        br.close()

    with open("/workspace/qa-pal-k3/ukuran_ui.json", "w") as f:
        json.dump(laporan, f, indent=2, ensure_ascii=False)
    print("\nditulis ukuran_ui.json", flush=True)


if __name__ == "__main__":
    main()
