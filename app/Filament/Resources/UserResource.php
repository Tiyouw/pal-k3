<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HanyaAdminBolehMenulis;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    use HanyaAdminBolehMenulis;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Petugas';

    protected static ?string $modelLabel = 'Petugas';

    protected static ?string $pluralModelLabel = 'Petugas';

    protected static ?string $navigationGroup = 'Data Induk';

    protected static ?int $navigationSort = 6;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nip')->label('NIP / username')->required()->maxLength(32)->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('name')->label('Nama')->required()->maxLength(255),
                Forms\Components\TextInput::make('email')->label('Surel')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                Forms\Components\Select::make('role')->label('Peran')->options(User::LABEL_ROLE)->required()
                    ->disabled(fn (?User $record): bool => $record?->id === auth()->id()),
                Forms\Components\Select::make('division_id')->label('Divisi')->relationship('division', 'nama')->searchable()->preload(),
                Forms\Components\TextInput::make('jabatan')->label('Jabatan')->maxLength(255),
                Forms\Components\TextInput::make('password')->label('Kata sandi')->password()->revealable()->autocomplete('new-password')
                    ->minLength(8)->maxLength(128)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->afterStateHydrated(fn (Forms\Components\TextInput $component) => $component->state(null))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Kosongkan saat mengubah petugas untuk mempertahankan kata sandinya.'),
                Forms\Components\Toggle::make('aktif')->label('Akun aktif')->default(true)
                    ->disabled(fn (?User $record): bool => $record?->id === auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nip')->label('NIP / username')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
                Tables\Columns\TextColumn::make('role')->label('Peran')->formatStateUsing(fn (string $state): string => User::LABEL_ROLE[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('division.nama')->label('Divisi')->placeholder('Belum ditetapkan'),
                Tables\Columns\IconColumn::make('aktif')->label('Aktif')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')->label('Peran')->options(User::LABEL_ROLE),
                Tables\Filters\TernaryFilter::make('aktif')->label('Akun aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
