<x-filament-panels::page>
    <div class="k3-dashboard">
        @if($pemantau)
            {{-- Pemantau: ringkasan untuk manajemen. Hanya baca; tidak ada antrean keputusan dan tidak ada tombol aksi tulis. --}}
            <div class="k3-mg">
                <section class="k3-card k3-ringkasan" aria-labelledby="judul-ringkasan">
                    <span class="k3-eyebrow">RINGKASAN</span>
                    <h2 id="judul-ringkasan">Ringkasan untuk manajemen</h2>
                    <p class="k3-narasi">{{ $narasi }}</p>
                    <div class="k3-num3">
                        <div><b>{{ number_format($persen, 1, ',', '.') }}%</b><small>kepatuhan</small></div>
                        <div><b>{{ $kondisi['tidak_layak'] }}</b><small>unit tidak layak</small></div>
                        <div><b>{{ $jumlah['lewat'] }}</b><small>temuan lewat tenggat</small></div>
                    </div>
                    <h3 class="k3-sub">Risiko terbuka</h3>
                    @forelse($risiko as $r)
                        <div class="k3-risiko">
                            <span class="k3-asset-code k3-asset-code-merah">{{ $r->asset?->kode }}</span>
                            <div>
                                <b>{{ $r->asset?->kode }} · {{ $r->asset?->labelLokasi() }}</b>
                                @if($r->severity === 'berat')<span class="k3-tag k3-tag-merah">Berat</span>@endif
                                @if($r->target_selesai && $r->target_selesai->isBefore(today()))<span class="k3-tag k3-tag-kuning">Lewat tenggat</span>@elseif($r->severity !== 'berat')<span class="k3-tag">Perlu perhatian</span>@endif
                                <p>{{ $r->item }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="k3-muted">Tidak ada risiko terbuka.</p>
                    @endforelse
                </section>
                <div class="k3-kol">
                    <section class="k3-card" aria-labelledby="judul-lap">
                        <h2 id="judul-lap">Laporan siap cetak</h2>
                        <ul class="k3-lap">
                            <li><div><b>Laporan PMS bulanan</b><small>Matriks ✓ X O Ø · {{ now()->translatedFormat('F Y') }}</small></div><a class="k3-button" href="{{ route('laporan.pms') }}" target="_blank" rel="noopener">Unduh PDF</a></li>
                            <li><div><b>Rekap temuan</b><small>{{ $risiko->isNotEmpty() ? 'Temuan yang belum selesai' : 'Belum ada temuan terbuka' }}</small></div><a class="k3-button k3-button-light" href="{{ route('laporan.temuan') }}" target="_blank" rel="noopener">Unduh</a></li>
                            <li><div><b>Kartu kontrol per unit</b><small>Pengganti kartu gantung, {{ $total }} unit</small></div><a class="k3-button k3-button-light" href="{{ route('laporan.index') }}">Pilih unit</a></li>
                        </ul>
                    </section>
                    <section class="k3-card k3-area k3-kepatuhan" aria-labelledby="judul-kepatuhan">
                        <h2 id="judul-kepatuhan">Kepatuhan {{ now()->translatedFormat('F') }}</h2>
                        <p class="k3-kepatuhan-besar">{{ number_format($persen, 1, ',', '.') }}%</p>
                        <p class="k3-kepatuhan-sub">{{ $diperiksa }} dari {{ $total }} unit · sisa {{ $hari }} hari</p>
                        <div class="k3-bar-list">
                            @forelse($area as $nama => $units)
                                @php($sudah = $units->filter(fn ($a) => $a->inspections->isNotEmpty())->count())
                                <div><span>{{ $nama }} <b>{{ $sudah }}/{{ $units->count() }}</b></span><i><u style="width:{{ $units->count() ? $sudah / $units->count() * 100 : 0 }}%"></u></i></div>
                            @empty
                                <p class="k3-kosong-gelap">Belum ada APAR aktif.</p>
                            @endforelse
                        </div>
                    </section>
                </div>
            </div>
        @else
            @php($pilihan = [
                'berat' => ['Temuan berat', 'butir', 'Perlu tindak lanjut'],
                'lewat' => ['Lewat tenggat', 'butir', 'Melampaui target selesai'],
                'posisi' => ['Tinjauan posisi', 'inspeksi', 'Menunggu verifikasi GPS'],
                'belum' => ['Belum diperiksa', 'unit', 'Sisa '.$hari.' hari bulan ini'],
            ])
            <div class="k3-stats">
                <a class="k3-stat k3-stat-primary" href="{{ route('laporan.index') }}"><span>Kepatuhan bulan ini</span><strong>{{ number_format($persen, 1, ',', '.') }}<small>%</small></strong><p>{{ $diperiksa }} dari {{ $total }} unit diperiksa</p><div class="k3-progress"><i style="width:{{ $persen }}%"></i></div><x-admin.apar class="k3-stat-apar" /></a>
                @foreach($pilihan as $key => [$label, $unit, $detail])
                    <button type="button" class="k3-stat k3-pick {{ $tab === $key ? 'is-selected' : '' }}" wire:click="pilihTab('{{ $key }}')" aria-pressed="{{ $tab === $key ? 'true' : 'false' }}"><span><i class="k3-status-dot k3-status-{{ $key }}"></i>{{ $label }}</span><strong>{{ $jumlah[$key] }} <small>{{ $unit }}</small></strong><p>{{ $detail }}</p></button>
                @endforeach
            </div>
            <section class="k3-card k3-queue" id="antrean"><div class="k3-card-head"><div><span class="k3-eyebrow">PERLU KEPUTUSAN ANDA</span><h2>{{ $pilihan[$tab][0] }}</h2></div></div>
                <div wire:loading.delay class="k3-muted" role="status">Memuat antrean…</div>
                <div class="k3-queue-list">
                @forelse($antrean as $record)
                    @php($aset = $tab === 'belum' ? $record : $record->asset)
                    <article class="k3-queue-row" wire:key="{{ $tab }}-{{ $record->id }}"><div class="k3-asset-code">{{ $aset->kode }}</div><div class="k3-queue-detail"><h3>{{ $aset->gedung }} @if($aset->labelLantai()) · {{ $aset->labelLantai() }} @endif</h3><p>{{ $tab === 'belum' ? $aset->labelLokasi() : ($tab === 'posisi' ? $record->labelGerbang() : $record->item) }}</p><small>
                        @if($tab === 'posisi'){{ $record->user?->name }} · {{ $record->inspected_at?->translatedFormat('d M, H.i') }} · Jarak {{ $record->jarak_m !== null ? $record->jarak_m.' m' : 'tidak tersedia' }} · Akurasi {{ $record->gps_accuracy !== null ? '±'.$record->gps_accuracy.' m' : 'tidak tersedia' }}
                        @elseif($tab !== 'belum')Target: {{ $record->target_selesai?->translatedFormat('d M Y') ?? 'Belum ditetapkan' }} · {{ ucfirst($record->status) }}
                        @else Belum ada inspeksi final bulan ini @endif</small></div><div class="k3-row-actions">
                        @if($tab === 'posisi')<a class="k3-link" href="{{ \App\Filament\Resources\InspectionResource::getUrl('view', ['record' => $record]) }}">Lihat bukti ↗</a><button class="k3-button" wire:click="mountAction('tinjau', { id: {{ $record->id }} })">Tinjau posisi</button>
                        @elseif(in_array($tab, ['berat', 'lewat']))<button class="k3-button k3-button-light" wire:click="mountAction('tenggat', { id: {{ $record->id }} })">Tetapkan tenggat</button><button class="k3-button" wire:click="mountAction('selesai', { id: {{ $record->id }} })">Tandai selesai</button>
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
        @endif

        <div class="k3-insights">
            <x-admin.kartu-tren :tren="$tren" :total="$total" />
            <x-admin.area-peta :area="$area" :kondisi="$kondisi" :jumlah="$jumlah" :diperiksa="$diperiksa" :total="$total" :peta="$peta" />
        </div>
    </div>
</x-filament-panels::page>
