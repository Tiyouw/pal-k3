#!/usr/bin/env python3
"""Uji demo dengan sesi browser nyata. Membuat dua inspeksi berlabel demonstrasi.

QA_DEMO_WRITE=1 wajib. QA_ASSET_FILE menunjuk JSON di luar repo berisi satu aset
contoh dengan qr_token, lat, dan lng. Jangan commit berkas tersebut atau hasil PDF.
QA_BASIS, QA_CHROMIUM, QA_TANGKAPAN mengikuti verifikasi_panel.py.
"""
import json
import os
import re
from pathlib import Path

from playwright.sync_api import expect, sync_playwright

BASIS = os.environ.get("QA_BASIS", "http://127.0.0.1:8130").rstrip("/")
OUTPUT = Path(os.environ["QA_TANGKAPAN"])
OUTPUT.mkdir(parents=True, exist_ok=True)
ASSET = json.loads(Path(os.environ["QA_ASSET_FILE"]).read_text(encoding="utf-8-sig"))
if os.environ.get("QA_DEMO_WRITE") != "1":
    raise SystemExit("Set QA_DEMO_WRITE=1 hanya untuk lingkungan demonstrasi.")


def login(page, username, password, panel=False):
    page.goto(BASIS + ("/admin/login" if panel else "/masuk"), wait_until="networkidle")
    expect(page.locator(".k3-demo")).to_be_visible()
    page.locator('input[autocomplete="username"]').fill(username)
    page.locator('input[autocomplete="current-password"]').fill(password)
    page.locator('button[type="submit"]').click()
    page.wait_for_url(re.compile(r"/admin/?$" if panel else r"/petugas/?$"))
    page.wait_for_load_state("networkidle")


def inspeksi(browser, bermasalah):
    ctx = browser.new_context(
        viewport={"width": 390, "height": 844}, is_mobile=True, has_touch=True,
        permissions=["geolocation"],
        geolocation={"latitude": float(ASSET["lat"]), "longitude": float(ASSET["lng"]), "accuracy": 5},
    )
    page = ctx.new_page()
    errors = []
    page.on("pageerror", lambda error: errors.append(str(error)))
    login(page, "inspektur", "inspektur123")
    page.goto(BASIS + "/petugas/pindai", wait_until="networkidle")
    page.locator("#kode").fill(ASSET["qr_token"])
    page.locator("#form-manual button[type=submit]").click()
    page.wait_for_url(re.compile(r"/petugas/inspeksi/\d+/?$"))
    page.wait_for_load_state("networkidle")
    record_id = int(page.url.rstrip("/").split("/")[-1])
    items = page.locator(".item[data-item]")
    assert items.count() == 10, "Checklist APAR harus berisi sepuluh butir."

    for index in range(items.count()):
        item = items.nth(index)
        option = item.locator('.opsi[data-nilai="ok"]')
        if option.count() == 0:
            option = item.locator(".opsi[data-nilai]").first
        if bermasalah and "Mulut pancar tidak tersumbat" in item.inner_text():
            option = item.locator('.opsi[data-nilai="tidak"]')
        with page.expect_response(lambda r: r.url.endswith("/jawaban") and r.request.method == "POST") as pending:
            option.click()
        result = pending.value
        assert result.ok and result.json()["tersimpan"], "Autosave jawaban gagal."
    expect(page.locator("#hitung")).to_have_text(re.compile(r"10\s*/\s*10"))

    initial_photos = page.locator("#galeri figure").count()
    with page.expect_response(lambda r: r.url.endswith("/foto") and r.request.method == "POST") as pending:
        page.locator("#berkas-foto").set_input_files(os.environ["QA_PHOTO"])
    result = pending.value
    assert result.ok, "Unggah foto gagal."
    photo_url = result.json()["url"]
    assert ctx.request.get(photo_url).status == 200, "Foto tersimpan tetapi URL-nya tidak bisa dibuka."
    expect(page.locator("#galeri figure")).to_have_count(initial_photos + 1)
    page.wait_for_function("() => document.querySelector('#galeri img')?.naturalWidth > 0")

    conclusion = "tidak_layak" if bermasalah else "layak"
    page.locator(f'[data-kesimpulan="{conclusion}"]').click()
    page.locator("#catatan").fill("DEMONSTRASI STAKEHOLDER: data dan foto contoh, bukan pemeriksaan operasional.")
    if bermasalah:
        page.locator("#rekomendasi").fill("DEMO: bersihkan mulut pancar dan verifikasi ulang kondisi APAR.")
    try:
        with page.expect_response(lambda r: r.url.endswith("/kirim") and r.request.method == "POST") as pending:
            page.locator("#tbl-kirim").click()
        result = pending.value
        if result.status != 302 or not result.headers.get("location", "").endswith("/selesai"):
            page.wait_for_load_state("networkidle")
            raise AssertionError(f"Pengiriman ditolak: HTTP {result.status}, pesan {page.locator('.galat, [role=alert]').all_inner_texts()}")
        page.wait_for_url(re.compile(r"/selesai$"))
    except Exception:
        page.screenshot(path=str(OUTPUT / "gagal-kirim.png"), full_page=True)
        raise
    expect(page.get_by_role("heading", name="Inspeksi tersimpan")).to_be_visible()
    expect(page.get_by_text("Tidak layak pakai" if bermasalah else "Layak pakai", exact=True)).to_be_visible()
    expect(page.get_by_text("Lokasi sesuai", exact=True)).to_be_visible()
    page.screenshot(path=str(OUTPUT / f"inspeksi-demo-{conclusion}.png"), full_page=True)
    page.goto(BASIS + "/petugas/riwayat", wait_until="networkidle")
    assert not errors, f"Galat JavaScript: {errors}"
    ctx.close()
    print(f"OK inspeksi {record_id}: QR manual, GPS, 10 autosave, foto publik, finalisasi, riwayat ({conclusion})", flush=True)
    return record_id


def laporan(browser, username, password, record_ids):
    ctx = browser.new_context(viewport={"width": 1366, "height": 768})
    page = ctx.new_page()
    login(page, username, password, panel=True)
    for path in ["/admin", "/admin/inspections", "/admin/issues", "/laporan"]:
        result = page.goto(BASIS + path, wait_until="networkidle")
        assert result.status == 200, f"{username}: {path} gagal."
    for name in ["pms", "temuan", "kartu/" + str(ASSET["id"])]:
        result = ctx.request.get(BASIS + "/laporan/" + name)
        assert result.status == 200 and result.body().startswith(b"%PDF"), f"PDF {name} gagal."
        (OUTPUT / f"{username}-{name.replace('/', '-')}.pdf").write_bytes(result.body())
    if username == "admin":
        for name in ["assets", "asset-types", "checklist-groups", "checklist-items", "divisions", "users"]:
            assert page.goto(BASIS + "/admin/" + name, wait_until="networkidle").status == 200
        for record_id in record_ids:
            assert page.goto(BASIS + "/admin/inspections/" + str(record_id), wait_until="networkidle").status == 200
        assert ctx.request.get(BASIS + "/stiker").status == 200
    else:
        assert ctx.request.get(BASIS + "/admin/users").status == 403
        assert ctx.request.get(BASIS + "/admin/assets/create").status == 403
    page.goto(BASIS + "/admin", wait_until="networkidle")
    page.screenshot(path=str(OUTPUT / f"demo-{username}-dashboard.png"), full_page=True)
    ctx.close()
    print(f"OK {username}: sesi login, menu, laporan PMS/temuan/kartu PDF, dan batas akses", flush=True)


with sync_playwright() as p:
    browser = p.chromium.launch(executable_path=os.environ.get("QA_CHROMIUM"))
    cases = [True] if os.environ.get("QA_CASE") == "berat" else [False, True]
    record_ids = [inspeksi(browser, case) for case in cases]
    laporan(browser, "admin", "admin123", record_ids)
    laporan(browser, "pemantau", "pemantau123", record_ids)
    browser.close()
print("LULUS: alur demo tiga peran, dua hasil inspeksi, foto, dan tiga jenis PDF.")
