@extends('layouts.petugas')

@section('judul', 'Inspeksi ' . $asset->kode)
@section('judul-kepala', $asset->kode)
@section('sub-kepala', $asset->lokasi_teks ?: trim($asset->gedung . ' ' . ($asset->lantai ? 'Lt. ' . $asset->lantai : '')))

@section('kembali')
    {{-- Tautan beranda, bukan tombol kembali peramban: draf tetap tersimpan
         sehingga meninggalkan halaman tidak menghilangkan pekerjaan. --}}
    <a href="{{ route('petugas.beranda') }}" aria-label="Simpan sebagai draf dan kembali ke beranda">&#8592;</a>
@endsection

@push('gaya')
<style>
    /* ---------- pengenal aset ---------- */
    .identitas { background: var(--biru); color: #fff; border-radius: var(--radius); padding: 16px; margin-top: 12px; }
    .identitas dl { margin: 0; display: grid; grid-template-columns: auto 1fr; gap: 6px 14px; font-size: .88rem; }
    .identitas dt { opacity: .8; }
    .identitas dd { margin: 0; font-weight: 600; }

    /* ---------- item checklist ---------- */
    .item { border-top: 1px solid var(--garis); padding: 16px 0; }
    .item:first-of-type { border-top: 0; padding-top: 4px; }
    .item .no { font-weight: 700; color: var(--biru); margin-right: 6px; }
    .item .label { font-weight: 600; font-size: .98rem; display: block; margin-bottom: 4px; }
    .item .hukum { font-size: .76rem; color: var(--abu); display: block; margin-bottom: 10px; }

    /* Dua tombol besar bersisian, bukan kotak centang kecil: sasaran sentuh
       selebar setengah layar dapat ditekan dengan jempol bersarung tangan. */
    .pilih2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .pilih-n { display: grid; gap: 10px; }

    .opsi {
        min-height: var(--sentuh); border: 2px solid var(--garis); background: #fff;
        border-radius: 12px; font-size: .95rem; font-weight: 600; font-family: inherit;
        cursor: pointer; padding: 8px 12px; text-align: center; color: #1b1f23;
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .opsi:active { transform: scale(.98); }
    .opsi[aria-pressed=true].baik  { border-color: var(--hijau); background: var(--hijau-md); color: #0b4a2e; }
    .opsi[aria-pressed=true].buruk { border-color: var(--merah); background: var(--merah-md); color: #7a1c26; }
    .opsi[aria-pressed=true].netral{ border-color: var(--biru); background: var(--biru-muda); color: var(--biru-tua); }

    .kunci { position: absolute; opacity: 0; pointer-events: none; }

    .bekas-simpan { font-size: .75rem; color: var(--hijau); margin-top: 8px; min-height: 1em; }
    .catatan-item { margin-top: 10px; }
    .catatan-item summary {
        cursor: pointer; font-size: .82rem; color: var(--biru);
        min-height: 36px; display: flex; align-items: center;
    }

    /* ---------- foto ---------- */
    .galeri { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 12px; }
    .galeri figure { margin: 0; position: relative; aspect-ratio: 1; }
    .galeri img { width: 100%; height: 100%; object-fit: cover; border-radius: 10px; display: block; }
    .galeri button {
        position: absolute; top: 4px; right: 4px; width: 32px; height: 32px;
        border: 0; border-radius: 8px; background: rgba(0,0,0,.65); color: #fff;
        font-size: .9rem; cursor: pointer;
    }
</style>
@endpush

@section('isi')
    {{-- ============ pengenal aset (tahap 5) ============ --}}
    <div class="identitas">
        <div class="baris" style="margin-bottom:10px">
            <strong style="font-size:1.25rem">{{ $asset->kode }}</strong>
            <span class="lencana" style="background:rgba(255,255,255,.18); color:#fff">
                &#10003; QR terverifikasi
            </span>
        </div>
        <dl>
            <dt>Lokasi</dt>
            <dd>{{ $asset->lokasi_teks ?: '-' }}</dd>
            <dt>Gedung</dt>
            <dd>{{ $asset->gedung ?: '-' }}{{ $asset->lantai ? ', Lt. ' . $asset->lantai : '' }}</dd>
            @if ($asset->labelMedia())
                <dt>Jenis media</dt>
                <dd>{{ $asset->labelMedia() }}</dd>
            @endif
            <dt>Terakhir dicek</dt>
            <dd>{{ $asset->terakhir_dicek ? $asset->terakhir_dicek->translatedFormat('d M Y') : 'belum pernah' }}</dd>
        </dl>
    </div>

    {{-- ============ keadaan gerbang GPS (tahap 4) ============ --}}
    <div id="kartu-gps" class="pesan p-biru" role="status" aria-live="polite">
        <span id="pesan-gps">Mengambil posisi&hellip; Anda dapat mulai mengisi tanpa menunggu.</span>
    </div>

    <form method="POST" action="{{ route('petugas.inspeksi.kirim', $inspeksi) }}" id="form-kirim" novalidate>
        @csrf
        <input type="hidden" name="device_time" id="device_time">

        @error('kesimpulan') <div class="pesan p-merah" role="alert">{{ $message }}</div> @enderror
        @error('rekomendasi') <div class="pesan p-merah" role="alert">{{ $message }}</div> @enderror

        {{-- ============ checklist per grup (tahap 6) ============ --}}
        @foreach ($groups as $grup)
            <section class="kartu" aria-labelledby="grup-{{ $grup->id }}">
                <h2 id="grup-{{ $grup->id }}" style="font-size:1rem">
                    {{ $grup->kode }}. {{ $grup->nama }}
                </h2>

                @foreach ($grup->items as $item)
                    @php
                        $jwb   = $jawaban[$item->id] ?? null;
                        $nilai = $jwb?->nilai;
                        $nomor = $loop->parent->iteration > 1 ? null : null; // nomor global dihitung di bawah
                    @endphp

                    <div class="item" data-item="{{ $item->id }}" data-tipe="{{ $item->answer_type }}">
                        <span class="label" id="lbl-{{ $item->id }}">
                            <span class="no">{{ $item->urut }}.</span>{{ $item->label }}
                            @unless ($item->wajib)
                                <span class="redup" style="font-weight:400">(opsional)</span>
                            @endunless
                        </span>

                        @if ($item->dasar_hukum)
                            <span class="hukum">{{ $item->dasar_hukum }}</span>
                        @endif

                        @if ($item->answer_type === 'select')
                            {{-- Pilihan lebih dari dua ditumpuk tegak. Dua kolom akan
                                 memotong teks seperti "Berkarat berat" jadi sulit dibaca. --}}
                            <div class="pilih-n" role="group" aria-labelledby="lbl-{{ $item->id }}">
                                @foreach ($item->daftarPilihan() as $opsi)
                                    @php $sev = $item->severityUntukNilai($opsi); @endphp
                                    <button type="button"
                                            class="opsi {{ $sev === null ? 'baik' : ($sev === 'berat' ? 'buruk' : 'netral') }}"
                                            data-nilai="{{ $opsi }}"
                                            aria-pressed="{{ $nilai === $opsi ? 'true' : 'false' }}">
                                        <span>{{ $opsi }}</span>
                                        @if ($sev)
                                            <span class="lencana l-{{ $sev === 'berat' ? 'merah' : ($sev === 'sedang' ? 'kuning' : 'abu') }}"
                                                  style="font-size:.68rem">{{ $sev }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>

                        @elseif ($item->answer_type === 'number')
                            <div style="display:flex; align-items:center; gap:10px">
                                <input type="number" inputmode="numeric" min="0" step="1"
                                       class="angka" value="{{ $jwb?->nilai_angka }}"
                                       aria-labelledby="lbl-{{ $item->id }}"
                                       style="max-width:140px">
                                <span class="redup">
                                    dari {{ $item->jumlah_baku }} {{ $item->satuan ?: 'buah' }}
                                </span>
                            </div>

                        @else
                            {{-- fungsi / kondisi / boolean: dua tombol, label mengikuti
                                 dimensi penilaian sesuai Bagian 4.4. --}}
                            <div class="pilih2" role="group" aria-labelledby="lbl-{{ $item->id }}">
                                <button type="button" class="opsi baik" data-nilai="ok"
                                        aria-pressed="{{ $nilai === 'ok' ? 'true' : 'false' }}">
                                    {{ $item->labelNilai('ok') }}
                                </button>
                                <button type="button" class="opsi buruk" data-nilai="tidak"
                                        aria-pressed="{{ $nilai === 'tidak' ? 'true' : 'false' }}">
                                    {{ $item->labelNilai('tidak') }}
                                </button>
                            </div>
                        @endif

                        <details class="catatan-item" @if($jwb?->catatan) open @endif>
                            <summary>Tambah catatan</summary>
                            <textarea class="catatan" rows="2" maxlength="500"
                                      placeholder="Keterangan tambahan (opsional)"
                                      aria-label="Catatan untuk {{ $item->label }}">{{ $jwb?->catatan }}</textarea>
                        </details>

                        <p class="bekas-simpan" aria-live="polite"></p>
                    </div>
                @endforeach
            </section>
        @endforeach

        {{-- ============ foto bukti (tahap 7) ============ --}}
        <section class="kartu" aria-labelledby="judul-foto">
            <div class="baris" style="margin-bottom:4px">
                <h2 id="judul-foto" style="margin:0">Foto bukti</h2>
                <span class="lencana l-merah" id="lencana-foto">wajib</span>
            </div>
            <p class="redup" style="margin:0 0 12px">
                Ambil foto tabung beserta stiker QR pada dinding. Minimal satu foto.
            </p>

            {{--
                capture=environment membuka kamera belakang langsung, bukan galeri.
                Bagian 3.2 menuntut foto diambil di tempat, bukan dipilih dari
                berkas lama. Ini bukan penjagaan yang tak bisa ditembus, karena
                sebagian peramban tetap menawarkan galeri; penjagaan yang
                sesungguhnya adalah jendela waktu inspeksi dan pemeriksaan GPS
                pada berkas yang diunggah.
            --}}
            <input type="file" id="berkas-foto" accept="image/jpeg,image/png,image/webp"
                   capture="environment" class="kunci" tabindex="-1">

            <button type="button" id="tbl-foto" class="tbl tbl-garis">
                <span aria-hidden="true">&#128247;</span> Ambil foto
            </button>

            <p id="status-foto" class="redup" style="margin:10px 0 0; min-height:1.2em" role="status" aria-live="polite"></p>

            <div class="galeri" id="galeri">
                @foreach ($inspeksi->photos as $f)
                    <figure data-foto="{{ $f->id }}">
                        <img src="{{ $f->url() }}" alt="Foto bukti inspeksi {{ $asset->kode }}" loading="lazy">
                        <button type="button" class="hapus-foto" aria-label="Hapus foto ini">&#10005;</button>
                    </figure>
                @endforeach
            </div>
        </section>

        {{-- ============ kesimpulan (tahap 8) ============ --}}
        <section class="kartu" aria-labelledby="judul-kesimpulan">
            <h2 id="judul-kesimpulan">Kesimpulan</h2>

            {{-- Usulan dihitung dari isi checklist, lalu boleh ditimpa petugas.
                 Nilai berubah sendiri saat jawaban disimpan (lihat skrip). --}}
            <div id="usul-kesimpulan" class="pesan p-hijau" style="margin-top:0">
                Belum ada temuan pada pemeriksaan ini.
            </div>

            <div class="pilih-n" role="radiogroup" aria-labelledby="judul-kesimpulan" style="margin-top:12px">
                @foreach (\App\Models\Inspection::KESIMPULAN as $kode => $label)
                    <button type="button" class="opsi kesimpulan
                            {{ $kode === 'layak' ? 'baik' : ($kode === 'tidak_layak' ? 'buruk' : 'netral') }}"
                            data-kesimpulan="{{ $kode }}"
                            aria-pressed="{{ old('kesimpulan', $usulan) === $kode ? 'true' : 'false' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <input type="hidden" name="kesimpulan" id="kesimpulan" value="{{ old('kesimpulan', $usulan) }}">

            <div style="margin-top:16px">
                <label class="judul" for="rekomendasi">
                    Rekomendasi <span id="wajib-rekomendasi" class="lencana l-merah" style="display:none">wajib</span>
                </label>
                <textarea name="rekomendasi" id="rekomendasi" maxlength="1000"
                          placeholder="Tindakan yang perlu dilakukan atas temuan">{{ old('rekomendasi') }}</textarea>
            </div>

            <div style="margin-top:12px">
                <label class="judul" for="catatan">Catatan umum</label>
                <textarea name="catatan" id="catatan" maxlength="1000"
                          placeholder="Keterangan lain (opsional)">{{ old('catatan') }}</textarea>
            </div>
        </section>
    </form>

    {{-- Pembatalan diletakkan di luar formulir utama: tombol hapus tidak boleh
         bersebelahan dengan tombol kirim agar tidak salah tekan. --}}
    <form method="POST" action="{{ route('petugas.inspeksi.batal', $inspeksi) }}"
          onsubmit="return confirm('Buang draf inspeksi ini beserta foto yang sudah diunggah?')"
          style="margin-top:12px">
        @csrf
        @method('DELETE')
        <button type="submit" class="tbl tbl-abu" style="color:var(--merah)">Buang draf ini</button>
    </form>
@endsection

@section('palang')
    <div class="palang">
        <div class="bingkai-dalam">
            <div class="baris" style="margin-bottom:8px; font-size:.82rem">
                <span class="redup">Pemeriksaan terisi</span>
                <strong class="mono" id="hitung">
                    {{ $inspeksi->answers->filter(fn ($a) => $a->nilai !== null || $a->nilai_angka !== null)->count() }}
                    / {{ $groups->flatMap->items->count() }}
                </strong>
            </div>
            {{-- Atribut form menautkan tombol ke formulir di atasnya, sehingga
                 palang tetap melekat di bawah layar tanpa membungkusnya. --}}
            <button type="submit" form="form-kirim" class="tbl tbl-hijau" id="tbl-kirim">
                Kirim inspeksi
            </button>
        </div>
    </div>
@endsection

@push('skrip')
<script>
(function () {
    const URL_LOKASI  = '{{ route('petugas.inspeksi.lokasi', $inspeksi) }}';
    const URL_JAWABAN = '{{ route('petugas.inspeksi.jawaban', $inspeksi) }}';
    const URL_FOTO    = '{{ route('petugas.inspeksi.foto', $inspeksi) }}';
    const URL_HAPUS   = '{{ url('petugas/inspeksi/' . $inspeksi->id . '/foto') }}';

    const kirim = (url, isi, jsonBody) => fetch(url, {
        method: 'POST',
        headers: Object.assign(
            { 'X-CSRF-TOKEN': window.CSRF, 'Accept': 'application/json' },
            jsonBody ? { 'Content-Type': 'application/json' } : {},
        ),
        body: jsonBody ? JSON.stringify(isi) : isi,
    });

    /* ================= tahap 4: posisi ================= */

    const kartuGps = document.getElementById('kartu-gps');
    const pesanGps = document.getElementById('pesan-gps');

    function warnaGps(status) {
        kartuGps.className = 'pesan ' + ({
            sesuai:       'p-hijau',
            perlu_review: 'p-kuning',
            jauh:         'p-merah',
            gps_lemah:    'p-kuning',
        }[status] || 'p-biru');
    }

    if (!navigator.geolocation) {
        pesanGps.textContent = 'Perangkat tidak mendukung penentuan lokasi. Inspeksi tetap dapat dikirim.';
    } else {
        navigator.geolocation.getCurrentPosition(
            function (pos) {
                kirim(URL_LOKASI, {
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                    accuracy: pos.coords.accuracy,
                }, true)
                    .then(r => r.json())
                    .then(function (d) {
                        warnaGps(d.status);
                        pesanGps.textContent = d.pesan;
                    })
                    .catch(function () {
                        pesanGps.textContent = 'Posisi gagal dikirim ke peladen. Inspeksi tetap dapat dikirim.';
                    });
            },
            function (e) {
                // Izin ditolak bukan penghalang. Bagian 5.6: GPS adalah bukti,
                // bukan pemblokir, jadi petugas tidak boleh terkunci di sini.
                pesanGps.textContent = e.code === e.PERMISSION_DENIED
                    ? 'Izin lokasi ditolak. Inspeksi tetap dapat dikirim, namun akan ditandai untuk ditinjau Admin K3.'
                    : 'Posisi tidak diperoleh (sinyal lemah). Inspeksi tetap dapat dikirim.';
                warnaGps('gps_lemah');
            },
            // enableHighAccuracy meminta GPS perangkat keras, bukan perkiraan
            // dari jaringan seluler yang bisa keliru ratusan meter.
            { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 },
        );
    }

    /* ================= tahap 6: penyimpanan otomatis ================= */

    const hitung  = document.getElementById('hitung');
    const usulKot = document.getElementById('usul-kesimpulan');
    const wajibRek= document.getElementById('wajib-rekomendasi');

    const LABEL_USUL = {
        layak:         ['p-hijau',  'Belum ada temuan pada pemeriksaan ini.'],
        layak_catatan: ['p-kuning', 'Ada temuan ringan atau sedang. Usulan: Layak dengan catatan. Rekomendasi wajib diisi.'],
        tidak_layak:   ['p-merah',  'Ada temuan berat. Usulan: Tidak layak pakai, tabung perlu diturunkan dari layanan.'],
    };

    function perbaruiUsulan(usulan) {
        const [kelas, teks] = LABEL_USUL[usulan] || LABEL_USUL.layak;

        usulKot.className = 'pesan ' + kelas;
        usulKot.textContent = teks;
        wajibRek.style.display = usulan === 'layak' ? 'none' : 'inline-flex';

        // Pilihan kesimpulan hanya diikutkan selama petugas belum menyentuhnya
        // sendiri. Kalau sudah, pilihan manusia yang menang.
        if (!document.getElementById('kesimpulan').dataset.manual) {
            pilihKesimpulan(usulan, false);
        }
    }

    function simpanJawaban(kotak, muatan) {
        const bekas = kotak.querySelector('.bekas-simpan');

        bekas.style.color = 'var(--abu)';
        bekas.textContent = 'Menyimpan\u2026';

        kirim(URL_JAWABAN, Object.assign({ checklist_item_id: kotak.dataset.item }, muatan), true)
            .then(function (r) {
                if (!r.ok) throw new Error('gagal');
                return r.json();
            })
            .then(function (d) {
                bekas.style.color = 'var(--hijau)';
                bekas.textContent = '\u2713 Tersimpan';
                hitung.textContent = d.terisi + ' / ' + d.total;
                perbaruiUsulan(d.usulan);

                // Jejak dihapus setelah beberapa saat supaya layar tidak penuh
                // tulisan "tersimpan" pada sepuluh baris sekaligus.
                setTimeout(function () { bekas.textContent = ''; }, 2500);
            })
            .catch(function () {
                bekas.style.color = 'var(--merah)';
                bekas.textContent = '\u26A0 Gagal disimpan. Tekan ulang pilihan Anda.';
            });
    }

    document.querySelectorAll('.item').forEach(function (kotak) {
        const opsi    = kotak.querySelectorAll('.opsi[data-nilai]');
        const angka   = kotak.querySelector('.angka');
        const catatan = kotak.querySelector('.catatan');

        opsi.forEach(function (tbl) {
            tbl.addEventListener('click', function () {
                opsi.forEach(o => o.setAttribute('aria-pressed', 'false'));
                tbl.setAttribute('aria-pressed', 'true');

                if (navigator.vibrate) navigator.vibrate(30);

                simpanJawaban(kotak, {
                    nilai: tbl.dataset.nilai,
                    catatan: catatan ? catatan.value : null,
                });
            });
        });

        if (angka) {
            // change, bukan input: menyimpan pada setiap ketikan akan mengirim
            // "1", "14" saat petugas mengetik 14 dan membanjiri sambungan lemah.
            angka.addEventListener('change', function () {
                simpanJawaban(kotak, {
                    nilai_angka: angka.value === '' ? null : angka.value,
                    catatan: catatan ? catatan.value : null,
                });
            });
        }

        if (catatan) {
            let jeda;
            catatan.addEventListener('input', function () {
                clearTimeout(jeda);

                jeda = setTimeout(function () {
                    const terpilih = kotak.querySelector('.opsi[aria-pressed=true]');

                    // Catatan tanpa jawaban tidak dikirim: baris jawaban kosong
                    // akan dihitung sebagai sudah terisi di penghitung.
                    if (!terpilih && !(angka && angka.value !== '')) return;

                    simpanJawaban(kotak, {
                        nilai: terpilih ? terpilih.dataset.nilai : null,
                        nilai_angka: angka && angka.value !== '' ? angka.value : null,
                        catatan: catatan.value,
                    });
                }, 900);
            });
        }
    });

    /* ================= tahap 7: foto ================= */

    const berkas   = document.getElementById('berkas-foto');
    const tblFoto  = document.getElementById('tbl-foto');
    const statFoto = document.getElementById('status-foto');
    const galeri   = document.getElementById('galeri');
    const lencanaF = document.getElementById('lencana-foto');

    function perbaruiLencanaFoto() {
        const ada = galeri.querySelectorAll('figure').length;

        lencanaF.className = 'lencana ' + (ada ? 'l-hijau' : 'l-merah');
        lencanaF.textContent = ada ? ada + ' foto' : 'wajib';
    }

    perbaruiLencanaFoto();

    tblFoto.addEventListener('click', () => berkas.click());

    berkas.addEventListener('change', function () {
        const f = berkas.files[0];
        if (!f) return;

        tblFoto.disabled = true;
        statFoto.style.color = 'var(--abu)';
        statFoto.textContent = 'Mengunggah foto\u2026';

        const data = new FormData();
        data.append('foto', f);

        kirim(URL_FOTO, data, false)
            .then(r => r.json().then(d => ({ ok: r.ok, d })))
            .then(function (hasil) {
                if (!hasil.ok) throw new Error(hasil.d.pesan || 'Unggahan ditolak.');

                const fig = document.createElement('figure');
                fig.dataset.foto = hasil.d.id;
                fig.innerHTML =
                    '<img src="' + hasil.d.url + '" alt="Foto bukti inspeksi">' +
                    '<button type="button" class="hapus-foto" aria-label="Hapus foto ini">\u2715</button>';
                galeri.appendChild(fig);

                statFoto.style.color = 'var(--hijau)';
                statFoto.textContent = '\u2713 Foto tersimpan.';
                perbaruiLencanaFoto();
            })
            .catch(function (e) {
                statFoto.style.color = 'var(--merah)';
                statFoto.textContent = '\u26A0 ' + e.message;
            })
            .finally(function () {
                tblFoto.disabled = false;
                berkas.value = '';   // agar foto yang sama bisa diambil ulang
            });
    });

    galeri.addEventListener('click', function (ev) {
        const tombol = ev.target.closest('.hapus-foto');
        if (!tombol) return;

        const fig = tombol.closest('figure');
        if (!confirm('Hapus foto ini?')) return;

        fetch(URL_HAPUS + '/' + fig.dataset.foto, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': window.CSRF, 'Accept': 'application/json' },
        })
            .then(function (r) {
                if (!r.ok) throw new Error();
                fig.remove();
                perbaruiLencanaFoto();
            })
            .catch(function () {
                statFoto.style.color = 'var(--merah)';
                statFoto.textContent = '\u26A0 Foto gagal dihapus.';
            });
    });

    /* ================= tahap 8: kesimpulan ================= */

    const isianKesimpulan = document.getElementById('kesimpulan');
    const tombolKesimpulan = document.querySelectorAll('.opsi.kesimpulan');

    function pilihKesimpulan(kode, manual) {
        tombolKesimpulan.forEach(function (t) {
            t.setAttribute('aria-pressed', t.dataset.kesimpulan === kode ? 'true' : 'false');
        });

        isianKesimpulan.value = kode;
        if (manual) isianKesimpulan.dataset.manual = '1';
    }

    tombolKesimpulan.forEach(function (t) {
        t.addEventListener('click', () => pilihKesimpulan(t.dataset.kesimpulan, true));
    });

    perbaruiUsulan('{{ $usulan }}');

    /* ================= pengiriman ================= */

    document.getElementById('form-kirim').addEventListener('submit', function (ev) {
        // Waktu perangkat disertakan sebagai pembanding. Waktu resmi tetap
        // ditentukan peladen (Bagian 5.7).
        document.getElementById('device_time').value = new Date().toISOString().slice(0, 19).replace('T', ' ');

        if (galeri.querySelectorAll('figure').length === 0) {
            ev.preventDefault();
            statFoto.style.color = 'var(--merah)';
            statFoto.textContent = '\u26A0 Foto wajib diunggah minimal satu.';
            document.getElementById('judul-foto').scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const tbl = document.getElementById('tbl-kirim');
        tbl.disabled = true;
        tbl.textContent = 'Mengirim\u2026';
    });
})();
</script>
@endpush
