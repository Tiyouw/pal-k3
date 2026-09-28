<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HanyaAdminBolehMenulis;
use App\Filament\Resources\AssetResource\Pages;
use App\Models\Asset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Data induk aset. Scaffold bawaan menampilkan relasi sebagai angka id, yang
 * tidak dapat dipakai petugas administrasi; kolom di sini memakai nama.
 *
 * Dua hal yang tidak boleh diubah lewat formulir:
 *   qr_token  - hanya lewat aksi regenerasi, supaya stiker lama tidak mati
 *               tanpa jejak. Lihat komentar pada aksi di bawah.
 *   lokasi_tipe - menentukan radius bawaan, jadi disajikan sebagai pilihan
 *               berlabel, bukan kotak teks bebas.
 */
class AssetResource extends Resource
{
    use HanyaAdminBolehMenulis;

    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationLabel = 'Aset';

    protected static ?string $modelLabel = 'Aset';

    protected static ?string $pluralModelLabel = 'Aset';

    protected static ?string $navigationGroup = 'Data Induk';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'kode';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identitas')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('asset_type_id')
                        ->label('Tipe objek')
                        ->relationship('assetType', 'nama')
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('kode')
                        ->label('Nomor aset')
                        ->required()
                        ->maxLength(32)
                        ->helperText('Nomor yang tertera pada tabung, misalnya 01D.'),
                    Forms\Components\Select::make('division_id')
                        ->label('Divisi')
                        ->relationship('division', 'nama')
                        ->native(false)
                        ->helperText('Penanggung jawab yang menandatangani laporan PMS.'),
                    Forms\Components\Toggle::make('aktif')
                        ->label('Aktif')
                        ->default(true)
                        ->helperText('Aset nonaktif tidak dihitung dalam persen kepatuhan.'),
                ]),

            Forms\Components\Section::make('Penempatan')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('gedung')->label('Gedung')->maxLength(64),
                    Forms\Components\TextInput::make('lantai')->label('Lantai')->maxLength(16),
                    Forms\Components\TextInput::make('lokasi_teks')
                        ->label('Uraian lokasi')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\Select::make('lokasi_tipe')
                        ->label('Jenis lokasi')
                        ->options(Asset::LABEL_LOKASI)
                        ->required()
                        ->native(false)
                        ->live()
                        ->helperText('Menentukan radius bawaan gerbang GPS.'),
                    Forms\Components\Placeholder::make('radius_bawaan')
                        ->label('Radius bawaan jenis ini')
                        ->content(function (Forms\Get $get): string {
                            $tipe = $get('lokasi_tipe');
                            $r    = Asset::RADIUS_PER_LOKASI[$tipe] ?? null;

                            if ($tipe === null) {
                                return 'Pilih jenis lokasi lebih dahulu.';
                            }

                            return $r === null
                                ? 'Tidak ada gerbang GPS (objek bergerak).'
                                : "{$r} meter";
                        }),
                ]),

            Forms\Components\Section::make('Titik koordinat')
                ->columns(3)
                ->description('Kosongkan lat dan lng kalau titik belum diukur. Gerbang GPS otomatis dilewati, inspeksi tetap tersimpan lewat QR.')
                ->schema([
                    Forms\Components\TextInput::make('lat')
                        ->label('Lintang')
                        ->numeric()
                        ->step('0.0000001')
                        ->rules(['nullable', 'numeric', 'between:-90,90']),
                    Forms\Components\TextInput::make('lng')
                        ->label('Bujur')
                        ->numeric()
                        ->step('0.0000001')
                        ->rules(['nullable', 'numeric', 'between:-180,180']),
                    Forms\Components\TextInput::make('radius_m')
                        ->label('Radius khusus (m)')
                        ->numeric()
                        ->minValue(5)
                        ->maxValue(500)
                        ->helperText('Kosongkan agar mengikuti radius jenis lokasi.'),
                ]),

            Forms\Components\Section::make('Riwayat dan atribut')
                ->columns(2)
                ->schema([
                    Forms\Components\DatePicker::make('tgl_expired')->label('Tanggal kedaluwarsa'),
                    Forms\Components\DatePicker::make('terakhir_dicek')
                        ->label('Terakhir dicek')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Terisi otomatis saat inspeksi dikirim.'),
                    Forms\Components\KeyValue::make('attributes')
                        ->label('Atribut tambahan')
                        ->keyLabel('Nama')
                        ->valueLabel('Nilai')
                        ->columnSpanFull()
                        ->helperText('Untuk APAR: tipe dan kapasitas_kg.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('kode')
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('assetType.nama')
                    ->label('Tipe')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gedung')->label('Gedung')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('lantai')->label('Lt.')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('lokasi_teks')
                    ->label('Lokasi')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn (Asset $r) => $r->lokasi_teks),
                Tables\Columns\TextColumn::make('lokasi_tipe')
                    ->label('Jenis')
                    // Nama parameter closure WAJIB $state: Filament menyuntik
                    // argumen berdasarkan nama, bukan urutan. Nama lain gagal
                    // dengan BindingResolutionException saat tabel dirender.
                    ->formatStateUsing(fn (?string $state) => Asset::LABEL_LOKASI[$state] ?? '-')
                    ->toggleable(),
                // Radius efektif, bukan kolom mentah: kolom mentah sering kosong
                // karena ia override, dan kosong terbaca keliru sebagai "belum diatur".
                Tables\Columns\TextColumn::make('radius_m')
                    ->label('Radius')
                    ->state(function (Asset $r): string {
                        $r_ = $r->radiusEfektif();

                        if ($r_ === null) {
                            return 'tanpa gerbang';
                        }

                        return $r->radius_m === null ? "{$r_} m (bawaan)" : "{$r_} m (khusus)";
                    }),
                Tables\Columns\IconColumn::make('titik')
                    ->label('Titik')
                    ->state(fn (Asset $r) => $r->lat !== null && $r->lng !== null)
                    ->boolean()
                    ->tooltip(fn (Asset $r) => $r->lat === null ? 'Koordinat belum diukur' : "{$r->lat}, {$r->lng}"),
                Tables\Columns\TextColumn::make('division.nama')->label('Divisi')->toggleable()->sortable(),
                Tables\Columns\TextColumn::make('terakhir_dicek')
                    ->label('Terakhir dicek')
                    ->date('d/m/Y')
                    ->placeholder('belum pernah')
                    ->sortable(),
                Tables\Columns\IconColumn::make('aktif')->label('Aktif')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('qr_token')
                    ->label('Token stiker')
                    ->copyable()
                    ->fontFamily('mono')
                    ->limit(18)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('asset_type_id')
                    ->label('Tipe objek')
                    ->relationship('assetType', 'nama'),
                Tables\Filters\SelectFilter::make('gedung')
                    ->label('Gedung')
                    ->options(fn () => Asset::query()
                        ->whereNotNull('gedung')
                        ->distinct()
                        ->orderBy('gedung')
                        ->pluck('gedung', 'gedung')
                        ->all()),
                Tables\Filters\SelectFilter::make('lokasi_tipe')
                    ->label('Jenis lokasi')
                    ->options(Asset::LABEL_LOKASI),
                Tables\Filters\TernaryFilter::make('aktif')->label('Aktif'),
                Tables\Filters\Filter::make('tanpa_titik')
                    ->label('Belum punya koordinat')
                    ->query(fn ($q) => $q->whereNull('lat')->orWhereNull('lng')),
                Tables\Filters\Filter::make('jatuh_tempo')
                    ->label('Jatuh tempo inspeksi')
                    ->query(fn ($q) => $q->where('aktif', true)->where(
                        fn ($s) => $s->whereNull('terakhir_dicek')
                            ->orWhereRaw("terakhir_dicek < date('now', '-30 day')")
                    )),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                self::aksiRegenToken(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Regenerasi token stiker.
     *
     * Bukan tombol sepele: begitu token diganti, stiker yang tertempel di
     * dinding berhenti berfungsi dan inspektur akan berdiri di depan APAR
     * dengan pindaian yang ditolak. Karena itu aksi ini meminta konfirmasi
     * bertanda bahaya dan mengingatkan bahwa stiker wajib dicetak ulang.
     *
     * Alasan fiturnya tetap ada: token adalah kunci bukti kehadiran. Kalau
     * selembar stiker terfoto dan beredar, satu-satunya pemulihan adalah
     * menerbitkan token baru.
     */
    private static function aksiRegenToken(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('regen_token')
            ->label('Ganti token')
            ->icon('heroicon-o-arrow-path')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Ganti token stiker QR')
            ->modalDescription('Stiker yang sudah tertempel akan berhenti berfungsi dan wajib dicetak ulang. Lakukan hanya kalau stiker rusak, hilang, atau tokennya bocor.')
            ->modalSubmitActionLabel('Ya, terbitkan token baru')
            ->visible(fn () => auth()->user()?->isAdmin() ?? false)
            ->action(function (Asset $record): void {
                $record->forceFill(['qr_token' => Asset::buatToken()])->save();

                Notification::make()
                    ->title('Token baru diterbitkan')
                    ->body("Cetak ulang stiker untuk aset {$record->kode} dari menu Lembar Stiker.")
                    ->warning()
                    ->persistent()
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'edit'   => Pages\EditAsset::route('/{record}/edit'),
        ];
    }
}
