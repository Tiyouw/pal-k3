<x-filament-panels::page>
    <div class="k3-dashboard">
        @if($pemantau)
            <div class="k3-monitor"><div><span class="k3-eyebrow">AKSES PEMANTAU · HANYA BACA</span><h2>Gambaran keselamatan, siap dilaporkan.</h2><p>Pantau kondisi APAR dan unduh rekap pemeriksaan periode ini.</p></div><a class="k3-button" href="{{ route('laporan.index') }}">Buka pusat laporan ↗</a></div>
        @endif
        <div class="k3-stats">
            <a class="k3-stat k3-stat-primary" href="{{ route('laporan.index') }}"><span>Kepatuhan bulan ini</span><strong>{{ number_format($persen, 1, ',', '.') }}<small>%</small></strong><p>{{ $diperiksa }} dari {{ $total }} unit diperiksa</p><div class="k3-progress"><i style="width:{{ $persen }}%"></i></div><x-admin.apar class="k3-stat-apar" /></a>
            @foreach(['belum' => ['Belum diperiksa', 'unit', 'Sisa '.$hari.' hari bulan ini'], 'berat' => ['Temuan berat', 'butir', 'Memerlukan tindak lanjut'], 'lewat' => ['Lewat tenggat', 'butir', 'Melampaui target penyelesaian'], 'posisi' => ['Tinjauan posisi', 'inspeksi', 'Menunggu verifikasi bukti GPS']] as $key => [$label, $unit, $detail])
                <button type="button" class="k3-stat {{ $tab === $key ? 'is-selected' : '' }}" wire:click="pilihTab('{{ $key }}')"><span>{{ $label }} <i class="k3-status-dot k3-status-{{ $key }}"></i></span><strong>{{ $jumlah[$key] }} <small>{{ $unit }}</small></strong><p>{{ $detail }}</p><span class="k3-stat-link">Lihat antrean <span aria-hidden="true">↗</span></span></button>
            @endforeach
        </div>
        <div class="k3-insights">
            <section class="k3-card k3-trend"><div class="k3-card-head"><h2>Tren pemeriksaan</h2><span class="k3-chip">6 bulan</span></div><div class="k3-trend-total">{{ $diperiksa }} <span>/ {{ $total }} unit</span></div><p class="k3-muted">APAR aktif yang diperiksa · {{ now()->translatedFormat('F Y') }}</p>
                @php
                    $maks = max($total, $tren->max('jumlah'), 1);
                    $points = $tren->map(fn ($t, $i) => (30 + $i * 88).','. (145 - $t['jumlah'] / $maks * 115))->implode(' ');
                @endphp
                <svg class="k3-chart" viewBox="0 0 500 185" role="img" aria-label="Tren jumlah APAR aktif yang diperiksa selama enam bulan">
                    @foreach([30, 87, 145] as $y)<line x1="30" x2="470" y1="{{ $y }}" y2="{{ $y }}" stroke="#dde4e6" stroke-dasharray="3 5"/>@endforeach
                    <polygon points="30,145 {{ $points }} 470,145" fill="#e7eeee"/>
                    <polyline points="{{ $points }}" fill="none" stroke="#153d45" stroke-width="3" stroke-linejoin="round"/>
                    @foreach($tren as $t)
                        <circle cx="{{ 30 + $loop->index * 88 }}" cy="{{ 145 - $t['jumlah'] / $maks * 115 }}" r="4" fill="#f5b700" stroke="#153d45" stroke-width="2"/>
                        <text x="{{ 30 + $loop->index * 88 }}" y="{{ 132 - $t['jumlah'] / $maks * 115 }}" text-anchor="middle">{{ $t['jumlah'] }}</text>
                        <text x="{{ 30 + $loop->index * 88 }}" y="175" text-anchor="middle">{{ $t['bulan'] }}</text>
                    @endforeach
                </svg>
                <p class="k3-caption">Satu unit dihitung sekali setiap bulan.</p>
            </section>
            <section class="k3-card k3-area"><div class="k3-card-head"><h2>Sebaran per area</h2><span>{{ $total }} unit</span></div><div class="k3-area-grid">
                @forelse($area as $nama => $units)
                    <div class="k3-area-item"><div><h3>{{ $nama }}</h3><span>{{ $units->filter(fn ($a) => $a->inspections->isNotEmpty())->count() }}/{{ $units->count() }}</span></div><div class="k3-dots">
                    @foreach($units as $unit)
                        @php($status = $unit->inspections->first()?->kesimpulan ?? ($unit->inspections->isNotEmpty() ? 'tanpa_kesimpulan' : 'belum'))
                        <span class="k3-unit k3-unit-{{ $status }}" title="{{ $unit->kode }} · {{ \App\Models\Inspection::KESIMPULAN[$status] ?? ($status === 'belum' ? 'Belum diperiksa' : 'Tanpa kesimpulan') }}"><span class="sr-only">{{ $unit->kode }}: {{ \App\Models\Inspection::KESIMPULAN[$status] ?? ($status === 'belum' ? 'Belum diperiksa' : 'Tanpa kesimpulan') }}</span></span>
                    @endforeach</div></div>
                @empty<p>Belum ada APAR aktif. Tambahkan aset untuk mulai memantau.</p>@endforelse
                </div><div class="k3-legend"><span>● Layak</span><span>▲ Catatan</span><span>◆ Tidak layak</span><span>○ Belum</span></div>
            </section>
            <section class="k3-card k3-condition"><h2>Kondisi unit</h2>
                @php($layak = $diperiksa ? $kondisi['layak'] / $diperiksa * 100 : 0)
                @php($catatan = $diperiksa ? $kondisi['layak_catatan'] / $diperiksa * 100 : 0)
                @php($tidak = $diperiksa ? $kondisi['tidak_layak'] / $diperiksa * 100 : 0)
                <div class="k3-donut" style="background:conic-gradient(#19734d 0 {{ $layak }}%,#f5b700 {{ $layak }}% {{ $layak + $catatan }}%,#b72c40 {{ $layak + $catatan }}% {{ $layak + $catatan + $tidak }}%,#e5eaec 0)"><div><strong>{{ $diperiksa }}</strong><span>unit diperiksa</span></div></div>
                <ul>@foreach(\App\Models\Inspection::KESIMPULAN as $key => $label)<li><span><i class="k3-unit k3-unit-{{ $key }}"></i>{{ $label }}</span><strong>{{ $kondisi[$key] }}</strong></li>@endforeach
                @if($diperiksa > $kondisi->sum())<li><span>Tanpa kesimpulan</span><strong>{{ $diperiksa - $kondisi->sum() }}</strong></li>@endif</ul>
            </section>
        </div>
        <section class="k3-card k3-queue" id="antrean"><div class="k3-card-head"><div><span class="k3-eyebrow">TINDAK LANJUT</span><h2>{{ $pemantau ? 'Pantauan pemeriksaan' : 'Perlu keputusan Anda' }}</h2></div><span class="k3-chip">{{ $jumlah[$tab] }} {{ $tab === 'belum' ? 'unit' : 'antrean' }}</span></div>
            <div class="k3-tabs" aria-label="Jenis antrean">@foreach(['berat' => 'Temuan berat', 'lewat' => 'Lewat tenggat', 'posisi' => 'Tinjauan posisi', 'belum' => 'Belum diperiksa'] as $key => $label)<button type="button" wire:click="pilihTab('{{ $key }}')" aria-pressed="{{ $tab === $key ? 'true' : 'false' }}" class="{{ $tab === $key ? 'active' : '' }}">{{ $label }}<span>{{ $jumlah[$key] }}</span></button>@endforeach</div>
            <div wire:loading.delay class="k3-muted" role="status">Memuat antrean…</div>
            <div class="k3-queue-list">
            @forelse($antrean as $record)
                @php($aset = $tab === 'belum' ? $record : $record->asset)
                <article class="k3-queue-row" wire:key="{{ $tab }}-{{ $record->id }}"><div class="k3-asset-code">{{ $aset->kode }}</div><div class="k3-queue-detail"><h3>{{ $aset->gedung }} @if($aset->labelLantai()) · {{ $aset->labelLantai() }} @endif</h3><p>{{ $tab === 'belum' ? $aset->labelLokasi() : ($tab === 'posisi' ? $record->labelGerbang() : $record->item) }}</p><small>
                    @if($tab === 'posisi'){{ $record->user?->name }} · {{ $record->inspected_at?->translatedFormat('d M, H.i') }} · Jarak {{ $record->jarak_m !== null ? $record->jarak_m.' m' : 'tidak tersedia' }} · Akurasi {{ $record->gps_accuracy !== null ? '±'.$record->gps_accuracy.' m' : 'tidak tersedia' }}
                    @elseif($tab !== 'belum')Target: {{ $record->target_selesai?->translatedFormat('d M Y') ?? 'Belum ditetapkan' }} · {{ ucfirst($record->status) }}
                    @else Belum ada inspeksi final bulan ini @endif</small></div><div class="k3-row-actions">
                    @if($tab === 'posisi')<a class="k3-link" href="{{ \App\Filament\Resources\InspectionResource::getUrl('view', ['record' => $record]) }}">Lihat bukti ↗</a>@if(!$pemantau)<button class="k3-button" wire:click="mountAction('tinjau', { id: {{ $record->id }} })">Tinjau posisi</button>@endif
                    @elseif(in_array($tab, ['berat', 'lewat']) && !$pemantau)<button class="k3-button k3-button-light" wire:click="mountAction('tenggat', { id: {{ $record->id }} })">Tetapkan tenggat</button><button class="k3-button" wire:click="mountAction('selesai', { id: {{ $record->id }} })">Tandai selesai</button>
                    @elseif($tab === 'belum')<a class="k3-link" href="{{ route('laporan.kartu', $aset) }}">Kartu pemeriksaan ↗</a>@endif
                </div></article>
            @empty<div class="k3-empty"><span class="k3-empty-mark" aria-hidden="true">✓</span><h3>{{ $total === 0 ? 'Belum ada data APAR' : 'Tidak ada antrean di kategori ini' }}</h3><p>{{ $total === 0 ? 'Ringkasan akan terisi setelah aset dan inspeksi tersedia.' : 'Pilih kategori lain untuk melanjutkan pemantauan.' }}</p></div>@endforelse
            </div>
            @if($antrean->hasPages())
                <nav class="k3-pagination" aria-label="Halaman antrean">
                    <span>{{ $antrean->firstItem() }}–{{ $antrean->lastItem() }} dari {{ $antrean->total() }} entri</span>
                    <div><button type="button" class="k3-button k3-button-light" wire:click="previousPage" @disabled($antrean->onFirstPage())>Sebelumnya</button><span>{{ $antrean->currentPage() }} / {{ $antrean->lastPage() }}</span><button type="button" class="k3-button k3-button-light" wire:click="nextPage" @disabled(!$antrean->hasMorePages())>Berikutnya</button></div>
                </nav>
            @endif
        </section>
        <p class="k3-caption k3-dashboard-foot">Divisi K3LH · PT PAL Indonesia <span>Periode {{ now()->translatedFormat('F Y') }} · Data inspeksi final</span></p>
    </div>
</x-filament-panels::page>
