<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InspectionResource\Pages;
use App\Models\Asset;
use App\Models\Inspection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Inspeksi adalah bukti lapangan, bukan data induk.
 *
 * Karena itu hampir seluruh kolom di sini DIKUNCI: angka GPS, waktu, dan
 * jawaban checklist tidak boleh disunting dari belakang meja. Kalau admin
 * dapat mengubah jarak_m atau gate_status, seluruh rantai bukti kehadiran
 * kehilangan nilainya dan pertanyaan "bagaimana sistem ini mencegah
 * pengisian dari luar lokasi" tidak punya jawaban.
 *
 * Yang boleh ditambahkan admin hanyalah HASIL TINJAUAN: catatan_review,
 * ditinjau_pada, ditinjau_oleh. Temuan lapangan tetap utuh, keputusan
 * manusia dicatat di sebelahnya.
 */
class InspectionResource extends Resource
{
    protected static ?string $model = Inspection::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Inspeksi';

    protected static ?string $modelLabel = 'Inspeksi';

    protected static ?string $pluralModelLabel = 'Inspeksi';

    protected static ?string $navigationGroup = 'Kegiatan';

    protected static ?int $navigationSort = 1;

    /** Lencana navigasi memunculkan antrean yang menunggu keputusan manusia. */
    public static function getNavigationBadge(): ?string
    {
        $n = static::antreanTinjauan()->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /** Inspeksi final yang gerbangnya menyimpang dan belum ditinjau siapa pun. */
    public static function antreanTinjauan(): Builder
    {
        return Inspection::query()
            ->where('status', 'final')
            ->whereIn('gate_status', Asset::statusPerluTinjauan())
            ->whereNull('ditinjau_pada');
    }

    public static function canCreate(): bool
    {
        // Inspeksi hanya lahir dari lapangan lewat pindaian QR.
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Bukti lapangan (tidak dapat diubah)')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('asset.kode')->label('Aset')->disabled(),
                    Forms\Components\TextInput::make('user.name')->label('Inspektur')->disabled(),
                    Forms\Components\DateTimePicker::make('inspected_at')->label('Waktu kirim')->disabled(),
                    Forms\Components\TextInput::make('gps_lat')->label('Lintang alat')->disabled(),
                    Forms\Components\TextInput::make('gps_lng')->label('Bujur alat')->disabled(),
                    Forms\Components\TextInput::make('gps_accuracy')->label('Akurasi (m)')->disabled(),
                    Forms\Components\TextInput::make('jarak_m')->label('Jarak ke aset (m)')->disabled(),
                    Forms\Components\TextInput::make('gate_status')->label('Status gerbang')->disabled(),
                    Forms\Components\TextInput::make('durasi_detik')->label('Durasi (detik)')->disabled(),
                    Forms\Components\Textarea::make('catatan')->label('Catatan inspektur')->disabled()->columnSpanFull(),
                    Forms\Components\Textarea::make('rekomendasi')->label('Rekomendasi inspektur')->disabled()->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Hasil tinjauan admin')
                ->columns(2)
                ->description('Bagian ini milik peninjau. Isi alasan kalau penyimpangan lokasi dinilai wajar, misalnya GPS terhalang lambung kapal.')
                ->schema([
                    Forms\Components\Textarea::make('catatan_review')
                        ->label('Catatan tinjauan')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\DateTimePicker::make('ditinjau_pada')->label('Ditinjau pada')->disabled(),
                    Forms\Components\Select::make('ditinjau_oleh')
                        ->label('Ditinjau oleh')
                        ->relationship('peninjau', 'name')
                        ->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('inspected_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('inspected_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('asset.kode')
                    ->label('Aset')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('asset.lokasi_teks')
                    ->label('Lokasi')
                    ->limit(28)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Inspektur')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('qr_verified')
                    ->label('QR')
                    ->boolean()
                    ->tooltip('Aset dikenali lewat pindaian stiker'),
                Tables\Columns\TextColumn::make('gate_status')
                    ->label('Gerbang')
                    ->badge()
                    ->color(fn (Inspection $r) => $r->warnaGerbang())
                    ->formatStateUsing(fn (Inspection $r) => $r->labelGerbang()),
                Tables\Columns\TextColumn::make('jarak_m')
                    ->label('Jarak')
                    ->state(fn (Inspection $r) => $r->jarak_m === null ? '-' : "{$r->jarak_m} m")
                    ->description(fn (Inspection $r) => $r->gps_accuracy === null ? null : "akurasi {$r->gps_accuracy} m")
                    ->sortable(),
                Tables\Columns\TextColumn::make('kesimpulan')
                    ->label('Kesimpulan')
                    ->badge()
                    ->formatStateUsing(fn (Inspection $r) => $r->labelKesimpulan())
                    // Nama parameter WAJIB $state: Filament menyuntik argumen
                    // closure berdasarkan nama, bukan urutan. Parameter bertipe
                    // model (Inspection $r) aman karena diresolusi lewat tipe.
                    ->color(fn (?string $state) => match ($state) {
                        'layak'         => 'success',
                        'layak_catatan' => 'warning',
                        'tidak_layak'   => 'danger',
                        default         => 'gray',
                    }),
                Tables\Columns\TextColumn::make('durasi_detik')
                    ->label('Durasi')
                    ->state(fn (Inspection $r) => $r->durasi_detik === null ? '-' : "{$r->durasi_detik} s")
                    // Pengisian terlalu cepat ditandai, bukan ditolak: bisa jadi
                    // inspektur memang hafal, bisa jadi checklist diisi di kantin.
                    ->color(fn (Inspection $r) => $r->durasiTakWajar() ? 'danger' : null)
                    ->tooltip(fn (Inspection $r) => $r->durasiTakWajar()
                        ? 'Di bawah ' . Inspection::DURASI_WAJAR_DETIK . ' detik untuk 10 item'
                        : null)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state) => $state === 'final' ? 'success' : 'gray'),
                Tables\Columns\IconColumn::make('ditinjau_pada')
                    ->label('Ditinjau')
                    ->state(fn (Inspection $r) => $r->ditinjau_pada !== null)
                    ->boolean()
                    ->tooltip(fn (Inspection $r) => $r->catatan_review),
            ])
            ->filters([
                Tables\Filters\Filter::make('perlu_tinjauan')
                    ->label('Antre ditinjau')
                    ->query(fn (Builder $q) => $q
                        ->where('status', 'final')
                        ->whereIn('gate_status', Asset::statusPerluTinjauan())
                        ->whereNull('ditinjau_pada')),
                Tables\Filters\SelectFilter::make('gate_status')
                    ->label('Status gerbang')
                    ->options([
                        'sesuai'        => 'Lokasi sesuai',
                        'perlu_review'  => 'Perlu ditinjau',
                        'jauh'          => 'Jauh dari aset',
                        'gps_lemah'     => 'GPS lemah',
                        'tanpa_gps'     => 'Tanpa GPS',
                        'tidak_berlaku' => 'Objek bergerak',
                    ]),
                Tables\Filters\SelectFilter::make('kesimpulan')
                    ->label('Kesimpulan')
                    ->options(Inspection::KESIMPULAN),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Inspektur')
                    ->relationship('user', 'name'),
                Tables\Filters\Filter::make('durasi_singkat')
                    ->label('Durasi tak wajar')
                    ->query(fn (Builder $q) => $q
                        ->whereNotNull('durasi_detik')
                        ->where('durasi_detik', '<', Inspection::DURASI_WAJAR_DETIK)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                self::aksiTinjau(),
            ])
            ->bulkActions([]);
    }

    /**
     * Menandai penyimpangan lokasi sudah diperiksa manusia.
     *
     * Penandaan otomatis tidak boleh menjadi tuduhan: pelat baja dan atap
     * bengkel membelokkan sinyal, jadi selalu ada kasus sah di antrean.
     * Aksi ini menuntut alasan tertulis supaya keputusan dapat diaudit,
     * dan tidak pernah mengubah angka GPS aslinya.
     */
    private static function aksiTinjau(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('tinjau')
            ->label('Tandai ditinjau')
            ->icon('heroicon-o-check-badge')
            ->color('warning')
            ->visible(fn (Inspection $r) => (auth()->user()?->isAdmin() ?? false)
                && $r->ditinjau_pada === null
                && $r->perluTinjauan())
            ->form([
                Forms\Components\Textarea::make('catatan_review')
                    ->label('Alasan atau hasil pemeriksaan')
                    ->required()
                    ->minLength(10)
                    ->rows(3)
                    ->helperText('Contoh: sinyal terhalang lambung kapal, kehadiran dipastikan lewat stiker QR.'),
            ])
            ->action(function (Inspection $record, array $data): void {
                $record->forceFill([
                    'catatan_review' => $data['catatan_review'],
                    'ditinjau_pada'  => now(),
                    'ditinjau_oleh'  => auth()->id(),
                ])->save();

                Notification::make()
                    ->title('Penyimpangan ditandai sudah ditinjau')
                    ->success()
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInspections::route('/'),
            'view'  => Pages\ViewInspection::route('/{record}'),
            'edit'  => Pages\EditInspection::route('/{record}/edit'),
        ];
    }
}
