@props(['tren', 'total'])
@php
    $maks = max($total, $tren->max('jumlah'), 1);
    $titik = $tren->map(fn ($t, $i) => (30 + $i * 88).','.(145 - $t['jumlah'] / $maks * 115))->implode(' ');
@endphp
<section class="k3-card k3-trend">
    <div class="k3-card-head"><h2>Tren pemeriksaan</h2><span class="k3-chip">6 bulan</span></div>
    <svg class="k3-chart" viewBox="0 0 500 185" role="img" aria-label="Tren jumlah APAR aktif yang diperiksa selama enam bulan">
        @foreach([30, 87, 145] as $y)<line x1="30" x2="470" y1="{{ $y }}" y2="{{ $y }}" stroke="#dde4e6" stroke-dasharray="3 5"/>@endforeach
        <polygon points="30,145 {{ $titik }} 470,145" fill="#e7eeee"/>
        <polyline points="{{ $titik }}" fill="none" stroke="#153d45" stroke-width="3" stroke-linejoin="round"/>
        @foreach($tren as $t)
            <circle cx="{{ 30 + $loop->index * 88 }}" cy="{{ 145 - $t['jumlah'] / $maks * 115 }}" r="4" fill="#f5b700" stroke="#153d45" stroke-width="2"/>
            <text x="{{ 30 + $loop->index * 88 }}" y="{{ 132 - $t['jumlah'] / $maks * 115 }}" text-anchor="middle">{{ $t['jumlah'] }}</text>
            <text x="{{ 30 + $loop->index * 88 }}" y="175" text-anchor="middle">{{ $t['bulan'] }}</text>
        @endforeach
    </svg>
    <p class="k3-caption">Jumlah APAR aktif yang diperiksa per bulan. Satu unit dihitung sekali.</p>
</section>
