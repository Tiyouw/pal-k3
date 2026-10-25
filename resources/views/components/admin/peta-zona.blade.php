@props(['zona'])
@php
    $kesimpulan = \App\Models\Inspection::KESIMPULAN;
    $labelStatus = fn (string $s): string => $kesimpulan[$s] ?? ($s === 'belum' ? 'Belum diperiksa' : 'Tanpa kesimpulan');
@endphp
{{--
    Peta skematik (bukan skala) satu zona. Koordinat x/y sudah diproyeksikan oleh RingkasanDasbor::peta().
    Bentuk titik membedakan status tanpa mengandalkan warna: lingkaran = layak, segitiga = layak dengan
    catatan, wajik = tidak layak, cincin = belum diperiksa. Cincin putus-putus biru = menunggu tinjauan posisi.
--}}
<svg viewBox="0 0 {{ $zona['lebar'] }} {{ $zona['tinggi'] }}" class="k3-peta-svg" role="group" aria-label="Peta skematik {{ count($zona['titik']) }} APAR di {{ $zona['nama'] }}, bukan skala">
    @foreach($zona['label'] as $label)
        <text class="k3-peta-label" x="{{ $label['x'] }}" y="{{ $label['y'] }}" text-anchor="middle">{{ $label['teks'] }}</text>
    @endforeach
    @foreach($zona['titik'] as $t)
        @php
            $info = ['kode' => $t['kode'], 'area' => $t['area'], 'lokasi' => $t['lokasi'], 'label' => $labelStatus($t['status']), 'tinjau' => $t['tinjau']];
        @endphp
        <g class="k3-ttk"
           transform="translate({{ $t['x'] }},{{ $t['y'] }})"
           tabindex="0" role="button"
           aria-label="{{ $t['kode'] }}, {{ $t['area'] }}: {{ $labelStatus($t['status']) }}@if($t['tinjau']), menunggu tinjauan posisi @endif"
           x-on:click="pilih = @js($info)"
           x-on:keydown.enter.prevent="pilih = @js($info)"
           x-on:keydown.space.prevent="pilih = @js($info)"
           x-bind:class="{ 'is-pilih': pilih && pilih.kode === @js($t['kode']) }">
            <circle r="12" fill="transparent"/>
            @if($t['tinjau'])<circle r="9.5" fill="none" stroke="#8fb8ff" stroke-width="1.3" stroke-dasharray="2.5 2.5"/>@endif
            @switch($t['status'])
                @case('tidak_layak')<rect x="-5" y="-5" width="10" height="10" rx="1.5" fill="#f06a7c" transform="rotate(45)"/>@break
                @case('layak_catatan')<path d="M0 -6.5 L6.5 5.5 L-6.5 5.5 Z" fill="#f5b700" stroke="#f5b700" stroke-width="1.2" stroke-linejoin="round"/>@break
                @case('belum')<circle r="4.5" fill="none" stroke="#b5cbd0" stroke-width="1.8"/>@break
                @case('tanpa_kesimpulan')<circle r="5" fill="#8ea6ad"/>@break
                @default<circle r="5.5" fill="#33cd88"/>
            @endswitch
            <circle class="k3-hl" r="11" fill="none"/>
        </g>
    @endforeach
</svg>
