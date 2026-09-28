#!/usr/bin/env python3
"""Verifikasi perbaikan UI/UX di situs hidup.

Tujuannya membuktikan, bukan mengasumsikan. Tiap temuan QA diubah jadi satu
pemeriksaan: string cacat lama HARUS hilang dari teks halaman, dan string
pengganti HARUS muncul. Kalau satu saja gagal, keluar dengan kode bukan nol
supaya tak mungkin dilaporkan "sudah beres" tanpa bukti.

Kebocoran wewenang diuji dengan cara membaca halaman ubah aset sebagai
pemantau: tombol Simpan dan Hapus tak boleh ada di sana.
"""
import json
import sys

from playwright.sync_api import sync_playwright

from harness import (AKUN, BASIS, MEJA, PONSEL, buat_konteks, buka, catatan,
                     masuk, pasang_konsol, rekam)

# (nama pemeriksaan, teks yang WAJIB hilang, teks yang WAJIB ada)
hasil = []


def cek(nama, teks, dilarang=(), diwajibkan=()):
    rapat = " ".join(teks.split())
    gagal = []
    for d in dilarang:
        if d in rapat:
            gagal.append(f"masih ada: {d!r}")
    for w in diwajibkan:
        if w not in rapat:
            gagal.append(f"tak ditemukan: {w!r}")
    hasil.append({"pemeriksaan": nama, "lulus": not gagal, "gagal": gagal})
    print(("  LULUS " if not gagal else "  GAGAL ") + nama, flush=True)
    for g in gagal:
        print("         " + g, flush=True)


def jalan():
    with sync_playwright() as pw:
        # ---------- lane inspektur di ponsel ----------
        br, ctx = buat_konteks(pw, PONSEL)
        hal = ctx.new_page()
        galat = pasang_konsol(hal)

        buka(hal, "/alamat-ngawur-xyz", galat, "vf-404-tamu")
        cek("404 tamu berbahasa Indonesia + jalan pulang",
            hal.inner_text("body"),
            dilarang=["Not Found", "404 |"],
            diwajibkan=["Halaman tidak ditemukan", "Masuk dengan NIP"])

        masuk(hal, galat, "inspektur", "vf-insp")
        cek("dasbor petugas: titik tengah tak mentah",
            hal.inner_text("body"),
            dilarang=["&middot;", "LT. LT.", "Lt. -"],
            diwajibkan=["NIP 2001"])

        buka(hal, "/alamat-ngawur-xyz", galat, "vf-404-inspektur")
        cek("404 inspektur menawarkan tugas + pemindai",
            hal.inner_text("body"),
            diwajibkan=["Kembali ke daftar tugas", "Buka pemindai QR"])

        buka(hal, "/stiker", galat, "vf-403-inspektur")
        cek("403 inspektur menjelaskan sebabnya",
            hal.inner_text("body"),
            dilarang=["Forbidden", "This action is unauthorized"],
            diwajibkan=["Tidak punya wewenang", "Kembali ke daftar tugas"])
        ctx.close()
        br.close()

        # ---------- lane pemantau di meja ----------
        br, ctx = buat_konteks(pw, MEJA)
        hal = ctx.new_page()
        galat = pasang_konsol(hal)

        masuk(hal, galat, "pemantau", "vf-pantau")
        cek("panel: judul dan sidebar tak berbau kerangka kerja",
            hal.inner_text("body") + " || JUDUL: " + hal.title(),
            dilarang=["- Laravel", "Asset Type", "Division", "Issue",
                      "Dokumentasi", "v3.3."],
            diwajibkan=["Inspeksi K3 APAR"])
        cek("angka sisa hari dibulatkan",
            hal.inner_text("body"),
            dilarang=["2.88", "0000000", ".99999"],
            diwajibkan=["hari bulan ini"])

        # Kebocoran wewenang: buka form ubah aset sebagai pemantau.
        buka(hal, "/admin/assets/1/edit", galat, "vf-pantau-ubah-aset")
        teks = hal.inner_text("body")
        tombol = hal.locator("button:has-text('Simpan'), button:has-text('Hapus')")
        jml = tombol.count()
        lolos = jml == 0
        hasil.append({"pemeriksaan": "pemantau tak punya tombol tulis di ubah aset",
                      "lulus": lolos,
                      "gagal": [] if lolos else [f"masih ada {jml} tombol tulis"]})
        print(("  LULUS " if lolos else "  GAGAL ")
              + f"pemantau tak punya tombol tulis di ubah aset (jml={jml})", flush=True)

        buka(hal, "/admin/assets", galat, "vf-pantau-daftar-aset")
        cek("daftar aset masih terbaca oleh pemantau",
            hal.inner_text("body"),
            dilarang=["LT. LT.", "&middot;"],
            diwajibkan=["Gedung"])
        ctx.close()
        br.close()

        # ---------- lane admin: stiker fisik ----------
        br, ctx = buat_konteks(pw, MEJA)
        hal = ctx.new_page()
        galat = pasang_konsol(hal)

        masuk(hal, galat, "admin", "vf-admin")
        buka(hal, "/stiker", galat, "vf-admin-stiker")
        cek("lembar stiker bersih sebelum dicetak ulang",
            hal.inner_text("body"),
            dilarang=["&middot;", "Lt. -", "LT. LT."],
            diwajibkan=["Pindai sebelum memeriksa"])

        buka(hal, "/admin/assets/1/edit", galat, "vf-admin-ubah-aset")
        tombol = hal.locator("button:has-text('Simpan')")
        ada = tombol.count() > 0
        hasil.append({"pemeriksaan": "admin tetap punya tombol simpan",
                      "lulus": ada,
                      "gagal": [] if ada else ["tombol Simpan hilang untuk admin"]})
        print(("  LULUS " if ada else "  GAGAL ") + "admin tetap punya tombol simpan",
              flush=True)
        ctx.close()
        br.close()


if __name__ == "__main__":
    jalan()
    with open("hasil_verifikasi.json", "w") as f:
        json.dump({"pemeriksaan": hasil, "tangkapan": catatan}, f,
                  ensure_ascii=False, indent=1)

    gagal = [h for h in hasil if not h["lulus"]]
    print(f"\nRINGKAS: {len(hasil) - len(gagal)}/{len(hasil)} lulus", flush=True)
    for g in gagal:
        print("GAGAL: " + g["pemeriksaan"] + " -> " + "; ".join(g["gagal"]), flush=True)
    sys.exit(1 if gagal else 0)
