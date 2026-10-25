<x-filament-panels::page>
    @php($pilihan = [
        'berat' => ['Temuan berat', 'butir', 'Perlu tindak lanjut'],
        'lewat' => ['Lewat tenggat', 'butir', 'Melampaui target selesai'],
        'posisi' => ['Tinjauan posisi', 'inspeksi', 'Menunggu verifikasi GPS'],
        'belum' => ['Belum diperiksa', 'unit', 'Sisa '.$hari.' hari bulan ini'],
    ])
    <div class="k3-dashboard">
        <div class="k3-stats">
            <a class="k3-stat k3-stat-primary" href="{{ route('laporan.index') }}"><span>Kepatuhan bulan ini</span><strong>{{ number_format($persen, 1, ',', '.') }}<small>%</small></strong><p>{{ $diperiksa }} dari {{ $total }} unit diperiksa</p><div class="k3-progress"><i style="width:{{ $persen }}%"></i></div><x-admin.apar class="k3-stat-apar" /></a>
            @foreach($pilihan as $key => [$label, $unit, $detail])
                <button type="button" class="k3-stat k3-pick {{ $tab === $key ? 'is-selected' : '' }}" wire:click="pilihTab('{{ $key }}')" aria-pressed="{{ $tab === $key ? 'true' : 'false' }}"><span><i class="k3-status-dot k3-status-{{ $key }}"></i>{{ $label }}</span><strong>{{ $jumlah[$key] }} <small>{{ $unit }}</small></strong><p>{{ $detail }}</p></button>
            @endforeach
        </div>
        <section class="k3-card k3-queue" id="antrean"><div class="k3-card-head"><div><span class="k3-eyebrow">{{ $pemantau ? 'PANTAUAN PEMERIKSAAN' : 'PERLU KEPUTUSAN ANDA' }}</span><h2>{{ $pilihan[$tab][0] }}</h2></div></div>
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
        <div class="k3-insights">
            <section class="k3-card k3-trend"><div class="k3-card-head"><h2>Tren pemeriksaan</h2><span class="k3-chip">6 bulan</span></div>
                @php($maks = max($total, $tren->max('jumlah'), 1))
                @php($points = $tren->map(fn ($t, $i) => (30 + $i * 88).','.(145 - $t['jumlah'] / $maks * 115))->implode(' '))
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
                <p class="k3-caption">Jumlah APAR aktif yang diperiksa per bulan. Satu unit dihitung sekali.</p>
            </section>
            <section class="k3-card k3-area"><div class="k3-card-head"><h2>Kondisi per area</h2><span>{{ $total }} unit</span></div><div class="k3-area-grid">
                @forelse($area as $nama => $units)
                    <div class="k3-area-item"><div><h3>{{ $nama }}</h3><span>{{ $units->filter(fn ($a) => $a->inspections->isNotEmpty())->count() }}/{{ $units->count() }}</span></div><div class="k3-dots">
                    @foreach($units as $unit)
                        @php($status = $unit->inspections->first()?->kesimpulan ?? ($unit->inspections->isNotEmpty() ? 'tanpa_kesimpulan' : 'belum'))
                        <span class="k3-unit k3-unit-{{ $status }}" title="{{ $unit->kode }} · {{ \App\Models\Inspection::KESIMPULAN[$status] ?? ($status === 'belum' ? 'Belum diperiksa' : 'Tanpa kesimpulan') }}"><span class="sr-only">{{ $unit->kode }}: {{ \App\Models\Inspection::KESIMPULAN[$status] ?? ($status === 'belum' ? 'Belum diperiksa' : 'Tanpa kesimpulan') }}</span></span>
                    @endforeach</div></div>
                @empty<p>Belum ada APAR aktif. Tambahkan aset untuk mulai memantau.</p>@endforelse
                </div>
                <ul class="k3-legend">
                    @foreach(\App\Models\Inspection::KESIMPULAN as $key => $label)<li><i class="k3-unit k3-unit-{{ $key }}"></i>{{ $label }} <b>{{ $kondisi[$key] }}</b></li>@endforeach
                    @if($diperiksa > $kondisi->sum())<li><i class="k3-unit k3-unit-tanpa_kesimpulan"></i>Tanpa kesimpulan <b>{{ $diperiksa - $kondisi->sum() }}</b></li>@endif
                    <li><i class="k3-unit k3-unit-belum"></i>Belum diperiksa <b>{{ $jumlah['belum'] }}</b></li>
                </ul>
            </section>
        </div>
    </div>
</x-filament-panels::page>
