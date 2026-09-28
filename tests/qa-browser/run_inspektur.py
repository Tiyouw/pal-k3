#!/usr/bin/env python3
"""Lane INSPEKTUR: alur petugas di depan tabung APAR, viewport ponsel.

Tak ada aksi merusak di sini: tak menghapus, tak mengganti token QR.
Draf inspeksi yang terbentuk dicatat supaya bisa dibersihkan sesudahnya.
"""
import json
import sys

from playwright.sync_api import sync_playwright

sys.path.insert(0, "/workspace/qa-pal-k3")
from harness import (BASIS, MEJA, PONSEL, QR_HIDUP, QR_MATI, buat_konteks,
                     buka, catatan, masuk, pasang_konsol, rekam)


def jalan():
    with sync_playwright() as pw:
        br, ctx = buat_konteks(pw, PONSEL)
        hal = ctx.new_page()
        galat = pasang_konsol(hal)

        # A. kesan pertama tanpa login
        buka(hal, "/", galat, "insp-01-beranda-publik",
             "halaman depan, belum login, di ponsel")

        # B. rute terlindungi tanpa login -> harus dialihkan ke /masuk
        buka(hal, "/petugas", galat, "insp-02-petugas-tanpa-login",
             "harus dialihkan ke /masuk")

        # C. login gagal: NIP salah
        hal.goto(BASIS + "/masuk", wait_until="domcontentloaded")
        hal.wait_for_timeout(600)
        hal.fill("input[name=nip]", "9999")
        hal.fill("input[type=password]", "sandiNgawur")
        hal.click("button[type=submit]")
        hal.wait_for_timeout(1500)
        rekam(hal, "insp-03-nip-salah", galat,
              catat="NIP tak terdaftar -- pesan galatnya manusiawi?")

        # D. login gagal: kolom kosong (uji validasi sisi klien vs server)
        hal.goto(BASIS + "/masuk", wait_until="domcontentloaded")
        hal.wait_for_timeout(600)
        hal.click("button[type=submit]")
        hal.wait_for_timeout(1200)
        rekam(hal, "insp-04-kolom-kosong", galat,
              catat="submit kosong -- validasi muncul?")

        # E. sandi lama yang sudah dicabut HARUS gagal
        hal.goto(BASIS + "/masuk", wait_until="domcontentloaded")
        hal.wait_for_timeout(600)
        hal.fill("input[name=nip]", "2001")
        hal.fill("input[type=password]", "admin123")
        hal.click("button[type=submit]")
        hal.wait_for_timeout(1500)
        rekam(hal, "insp-05-sandi-lama-dicabut", galat,
              catat="admin123 harus DITOLAK")

        # F. login sah
        masuk(hal, galat, "inspektur", "insp-06")

        # G. dasbor + riwayat (0 inspeksi -> keadaan kosong)
        buka(hal, "/petugas", galat, "insp-07-dasbor",
             "dasbor petugas: aksi utama terlihat tanpa scroll?")
        buka(hal, "/petugas/riwayat", galat, "insp-08-riwayat-kosong",
             "0 inspeksi -- pesan bermakna atau layar hampa?")
        buka(hal, "/petugas/pindai", galat, "insp-09-pindai",
             "halaman pindai QR")

        # H. gerbang peran: /stiker bukan hak inspektur
        buka(hal, "/stiker", galat, "insp-10-stiker-harus-403",
             "harus 403 -- halaman penolakannya menolong?")

        # I. masuk dari stiker QR fisik, token hidup
        buka(hal, f"/i/{QR_HIDUP}", galat, "insp-11-qr-hidup",
             "titik masuk stiker QR yang sah")

        # ukur: berapa langkah dari sini sampai kirim
        try:
            hal.wait_for_timeout(1500)
            rekam(hal, "insp-12-form-inspeksi", galat,
                  catat="form checklist: gerbang GPS, jumlah butir, tombol kirim")
        except Exception as e:
            print(f"form inspeksi gagal: {e}")

        # J. token mati -> pesan gagal
        buka(hal, f"/i/{QR_MATI}", galat, "insp-13-qr-mati",
             "token lama sudah dicabut -- pesannya menolong?")

        # K. token ngawur
        buka(hal, "/i/PAL-K3-bukan-token-sama-sekali", galat,
             "insp-14-qr-ngawur", "token asing")

        # L. 404
        buka(hal, "/halaman-tidak-ada-xyz", galat, "insp-15-404",
             "halaman 404 -- ada jalan pulang?")

        ctx.close()
        br.close()

    with open("/workspace/qa-pal-k3/hasil_inspektur.json", "w") as f:
        json.dump(catatan, f, ensure_ascii=False, indent=1)
    print(f"\nselesai: {len(catatan)} tangkapan")
    kembar = [c["nama"] for c in catatan if c["kembar_dengan"]]
    print(f"tangkapan kembar: {len(kembar)} {kembar}")


if __name__ == "__main__":
    jalan()
