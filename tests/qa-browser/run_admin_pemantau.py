#!/usr/bin/env python3
"""Lane ADMIN + PEMANTAU, viewport meja 1440x900.

AMAN: hanya navigasi, tangkap, dan ketik di kolom cari. Tidak mengklik
aksi "Ganti token stiker QR" (mematikan 35 stiker fisik) dan tidak
menghapus baris apa pun.
"""
import json
import sys

from playwright.sync_api import sync_playwright

sys.path.insert(0, "/workspace/qa-pal-k3")
from harness import (BASIS, MEJA, buat_konteks, buka, catatan, masuk,
                     pasang_konsol, rekam)


def lane_admin(pw):
    br, ctx = buat_konteks(pw, MEJA)
    hal = ctx.new_page()
    galat = pasang_konsol(hal)

    masuk(hal, galat, "admin", "adm-01")
    buka(hal, "/admin", galat, "adm-02-dasbor",
         "dasbor admin: ada widget bermakna atau tabel hampa?")
    buka(hal, "/admin/assets", galat, "adm-03-aset",
         "35 APAR: lebar tabel, kolom terpotong, istilah Inggris?")

    # cari: apakah pencarian jalan dan labelnya Indonesia?
    try:
        cari = hal.locator("input[type=search], input[placeholder*='ari']").first
        cari.fill("01D")
        hal.wait_for_timeout(2000)
        rekam(hal, "adm-04-aset-cari", galat, catat="cari '01D' di tabel aset")
    except Exception as e:
        print(f"cari aset gagal: {e}")

    # buka form edit HANYA untuk menilai tata letak -- TIDAK menyimpan
    buka(hal, "/admin/assets/1/edit", galat, "adm-05-aset-edit",
         "form edit: tata letak. TIDAK disimpan. Aksi ganti token terlihat?")

    buka(hal, "/admin/inspections", galat, "adm-06-inspeksi-kosong",
         "0 baris: keadaan kosong menolong?")
    buka(hal, "/admin/issues", galat, "adm-07-temuan-kosong",
         "0 baris: keadaan kosong menolong?")
    buka(hal, "/admin/divisions", galat, "adm-08-divisi", "3 divisi")
    buka(hal, "/stiker", galat, "adm-09-stiker",
         "lembar stiker: jelas cara cetak? berapa per lembar?")
    buka(hal, "/laporan", galat, "adm-10-laporan", "indeks laporan")
    buka(hal, "/laporan/pms", galat, "adm-11-laporan-pms",
         "rekap PMS dengan 0 inspeksi")
    buka(hal, "/laporan/temuan", galat, "adm-12-laporan-temuan",
         "daftar temuan dengan 0 temuan")
    buka(hal, "/laporan/kartu/1", galat, "adm-13-kartu-aset",
         "kartu riwayat satu tabung, belum pernah diperiksa")

    # keadaan tepi
    buka(hal, "/laporan/kartu/99999", galat, "adm-14-kartu-tak-ada",
         "aset tak ada")
    buka(hal, "/laporan/kartu/abc", galat, "adm-15-kartu-id-bukan-angka",
         "ID bukan angka")

    ctx.close()
    br.close()


def lane_pemantau(pw):
    br, ctx = buat_konteks(pw, MEJA)
    hal = ctx.new_page()
    galat = pasang_konsol(hal)

    masuk(hal, galat, "pemantau", "pmt-01")

    # gerbang peran: mana yang sah, mana yang harus ditolak
    for jalur, nama, harap in [
        ("/laporan", "pmt-02-laporan", "sah"),
        ("/laporan/temuan", "pmt-03-temuan", "sah"),
        ("/admin", "pmt-04-admin", "sah"),
        ("/admin/assets", "pmt-05-admin-aset", "periksa"),
        ("/admin/divisions", "pmt-06-admin-divisi", "periksa"),
        ("/stiker", "pmt-07-stiker", "sah"),
        ("/petugas", "pmt-08-petugas", "harus ditolak"),
        ("/petugas/pindai", "pmt-09-pindai", "harus ditolak"),
    ]:
        buka(hal, jalur, galat, nama, f"hak pemantau: {harap}")

    ctx.close()
    br.close()


def jalan():
    with sync_playwright() as pw:
        lane_admin(pw)
        lane_pemantau(pw)
    with open("/workspace/qa-pal-k3/hasil_admin_pemantau.json", "w") as f:
        json.dump(catatan, f, ensure_ascii=False, indent=1)
    print(f"\nselesai: {len(catatan)} tangkapan")
    kembar = [c["nama"] for c in catatan if c["kembar_dengan"]]
    print(f"kembar: {len(kembar)} {kembar}")


if __name__ == "__main__":
    jalan()
