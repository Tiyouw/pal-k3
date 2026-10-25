@props(['area', 'kondisi', 'jumlah', 'diperiksa', 'total', 'peta'])
@php
    $label = \App\Models\Inspection::KESIMPULAN;
    $tanpaKesimpulan = $diperiksa - $kondisi->sum();
    $zona = $peta['zona'];
@endphp
{{--
    Satu kartu, dua tampilan: "Titik" (kondisi per area) dan "Peta". Pilihan terakhir diingat di peramban
    (Alpine $persist) dan tidak menyentuh basis data. Tampilan awal Titik. Keduanya sudah dirender peladen;
    Alpine hanya mengganti mana yang terlihat dan menyorot titik yang dipilih.
--}}
<section class="k3-card k3-area"
    x-data="{ mode: $persist('titik').as('k3DasborArea'), zona: 'Semua', pilih: null }"
    x-init="if (!['titik', 'peta'].includes(mode)) mode = 'titik'">
    <div class="k3-card-head">
        <h2 x-show="mode === 'titik'">Kondisi per area</h2>
        <h2 x-show="mode === 'peta'" x-cloak>Peta APAR</h2>
        <div class="k3-kepala-kanan">
            <span class="k3-area-jml" x-text="mode === 'peta' ? @js($total.' titik') : @js($total.' unit')">{{ $total }} unit</span>
            <div class="k3-sakelar" role="group" aria-label="Tampilan kartu">
                <button type="button" class="k3-sakelar-btn" x-on:click="mode = 'titik'" x-bind:aria-pressed="mode === 'titik'" x-bind:class="{ 'is-on': mode === 'titik' }" aria-label="Tampilan titik per area" title="Titik per area">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><circle cx="6" cy="6" r="2"/><circle cx="12" cy="6" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="6" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="18" cy="12" r="2"/><circle cx="6" cy="18" r="2"/><circle cx="12" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
                </button>
                <button type="button" class="k3-sakelar-btn" x-on:click="mode = 'peta'" x-bind:aria-pressed="mode === 'peta'" x-bind:class="{ 'is-on': mode === 'peta' }" aria-label="Tampilan peta" title="Peta">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M19 10c0 5.5-7 11-7 11S5 15.5 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                </button>
            </div>
        </div>
    </div>

    @if($total === 0)
        <p class="k3-kosong-gelap">Belum ada APAR aktif. Tambahkan aset untuk mulai memantau.</p>
    @else
        {{-- Tampilan titik --}}
        <div x-show="mode === 'titik'">
            <div class="k3-area-grid">
                @foreach($area as $nama => $units)
                    <div class="k3-area-item"><div><h3>{{ $nama }}</h3><span>{{ $units->filter(fn ($a) => $a->inspections->isNotEmpty())->count() }}/{{ $units->count() }}</span></div><div class="k3-dots">
                        @foreach($units as $unit)
                            @php($status = $unit->inspections->first()?->kesimpulan ?? ($unit->inspections->isNotEmpty() ? 'tanpa_kesimpulan' : 'belum'))
                            <span class="k3-unit k3-unit-{{ $status }}" title="{{ $unit->kode }} · {{ $label[$status] ?? ($status === 'belum' ? 'Belum diperiksa' : 'Tanpa kesimpulan') }}"><span class="sr-only">{{ $unit->kode }}: {{ $label[$status] ?? ($status === 'belum' ? 'Belum diperiksa' : 'Tanpa kesimpulan') }}</span></span>
                        @endforeach</div></div>
                @endforeach
            </div>
        </div>

        {{-- Tampilan peta --}}
        <div x-show="mode === 'peta'" x-cloak>
            <div class="k3-zona" role="group" aria-label="Zona peta">
                @foreach($zona as $nama => $z)
                    <button type="button" x-on:click="zona = @js($nama); pilih = null" x-bind:aria-pressed="zona === @js($nama)" x-bind:class="{ 'is-on': zona === @js($nama) }">{{ $nama === 'Semua' ? 'Semua' : $nama }} · {{ $z['diperiksa'] }}/{{ $z['total'] }}</button>
                @endforeach
            </div>
            <div class="k3-peta-kanvas">
                @foreach($zona as $nama => $z)
                    <div x-show="zona === @js($nama)" @if(! $loop->first) x-cloak @endif><x-admin.peta-zona :zona="$z" /></div>
                @endforeach
            </div>
            @if($peta['tanpa_titik'] > 0)
                <p class="k3-peta-catatan">{{ $peta['tanpa_titik'] }} unit belum punya koordinat, jadi tidak digambar di peta.</p>
            @endif
            <div class="k3-peta-info" aria-live="polite">
                <template x-if="pilih"><div><small x-text="pilih.area + ' · ' + pilih.lokasi"></small><b x-text="pilih.kode"></b> · <span x-text="pilih.label"></span><span x-show="pilih.tinjau"> · Perlu tinjauan posisi</span></div></template>
                <template x-if="!pilih"><div><small>Klik atau ketuk titik untuk melihat unit</small>Peta skematik, bukan skala. Titik bermasalah ditandai bentuk berbeda.</div></template>
            </div>
        </div>

        <ul class="k3-legend">
            @foreach($label as $key => $teks)<li><i class="k3-unit k3-unit-{{ $key }}"></i>{{ $teks }} <b>{{ $kondisi[$key] }}</b></li>@endforeach
            @if($tanpaKesimpulan > 0)<li><i class="k3-unit k3-unit-tanpa_kesimpulan"></i>Tanpa kesimpulan <b>{{ $tanpaKesimpulan }}</b></li>@endif
            <li><i class="k3-unit k3-unit-belum"></i>Belum diperiksa <b>{{ $jumlah['belum'] }}</b></li>
            <li x-show="mode === 'peta'" x-cloak><i class="k3-unit k3-unit-tinjau"></i>Menunggu tinjauan posisi <b>{{ $jumlah['posisi'] }}</b></li>
        </ul>
    @endif
</section>
