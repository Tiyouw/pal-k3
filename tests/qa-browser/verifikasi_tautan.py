#!/usr/bin/env python3
"""Buktikan tautan silang pengelola benar ada di situs hidup.

Test feature sudah hijau, tapi test yang sama pernah memberi hijau palsu untuk
halaman 404. Jadi sidebar Filament diperiksa di peramban sungguhan: NavigationItem
dirender oleh Livewire, bukan oleh Blade biasa, sehingga assertSee() belum
membuktikan tautannya benar tampil.

Keluar dengan kode != 0 kalau ada pemeriksaan gagal.
"""
import sys

from playwright.sync_api import sync_playwright

import harness as H

gagal = []


def cek(nama, hal, diwajibkan=(), dilarang=()):
    teks = hal.inner_text("body")
    kurang = [t for t in diwajibkan if t not in teks]
    ada = [t for t in dilarang if t in teks]
    if kurang or ada:
        pesan = f"{nama} ->"
        if kurang:
            pesan += " tak ditemukan: " + ", ".join(repr(t) for t in kurang)
        if ada:
            pesan += " muncul padahal dilarang: " + ", ".join(repr(t) for t in ada)
        gagal.append(pesan)
        print(f"  GAGAL {pesan}", flush=True)
    else:
        print(f"  LULUS {nama}", flush=True)


def href_ada(hal, pola):
    """Hitung tautan yang href-nya memuat pola tertentu."""
    return hal.evaluate(
        "p => [...document.querySelectorAll('a[href]')]"
        ".filter(a => a.getAttribute('href').includes(p)).length",
        pola,
    )


def main():
    with sync_playwright() as pw:
        # --- admin: sidebar panel harus punya dua pintu baru ---
        br, ctx = H.buat_konteks(pw, H.MEJA)
        hal = ctx.new_page()
        galat = H.pasang_konsol(hal)

        H.masuk(hal, galat, "admin", "tl-admin")
        H.buka(hal, "/admin", galat, "tl-admin-panel")
        cek("sidebar panel memuat dua pintu pengelolaan", hal,
            diwajibkan=("Lembar Stiker QR", "Laporan Bulanan"))

        n_stiker = href_ada(hal, "/stiker")
        n_laporan = href_ada(hal, "/laporan")
        if n_stiker < 1 or n_laporan < 1:
            g = f"sidebar href -> stiker={n_stiker} laporan={n_laporan}, butuh >=1"
            gagal.append(g)
            print(f"  GAGAL {g}", flush=True)
        else:
            print(f"  LULUS sidebar punya href nyata (stiker={n_stiker} "
                  f"laporan={n_laporan})", flush=True)

        # --- /stiker tak lagi jalan buntu ---
        H.buka(hal, "/stiker", galat, "tl-admin-stiker")
        cek("kepala stiker menawarkan jalan keluar", hal,
            diwajibkan=("Panel admin", "Laporan bulanan"),
            dilarang=("&middot;", "Lt. -"))
        keluar = href_ada(hal, "/admin") + href_ada(hal, "/laporan")
        if keluar < 2:
            g = f"stiker hanya punya {keluar} tautan keluar, butuh >=2"
            gagal.append(g)
            print(f"  GAGAL {g}", flush=True)
        else:
            print(f"  LULUS stiker punya {keluar} tautan keluar", flush=True)

        # --- /laporan arah balik ---
        H.buka(hal, "/laporan", galat, "tl-admin-laporan")
        cek("laporan menawarkan jalan ke stiker dan panel", hal,
            diwajibkan=("Lembar stiker QR", "Panel admin"))
        br.close()

        # --- inspektur: tautan tak boleh jadi celah wewenang ---
        br2, ctx2 = H.buat_konteks(pw, H.PONSEL)
        hal2 = ctx2.new_page()
        galat2 = H.pasang_konsol(hal2)
        H.masuk(hal2, galat2, "inspektur", "tl-insp")
        item = H.buka(hal2, "/stiker", galat2, "tl-insp-stiker")
        if item["status_http"] != 403:
            g = f"inspektur dapat {item['status_http']} di /stiker, harus 403"
            gagal.append(g)
            print(f"  GAGAL {g}", flush=True)
        else:
            print("  LULUS inspektur tetap 403 di /stiker", flush=True)
        br2.close()

    total = 6
    print(f"\nRINGKAS: {total - len(gagal)}/{total} lulus", flush=True)
    for g in gagal:
        print(f"GAGAL: {g}", flush=True)
    sys.exit(1 if gagal else 0)


if __name__ == "__main__":
    main()
