<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetResource\Pages;
use App\Filament\Resources\AssetResource\RelationManagers;
use App\Models\Asset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('asset_type_id')
                    ->relationship('assetType', 'id')
                    ->required(),
                Forms\Components\Select::make('division_id')
                    ->relationship('division', 'id'),
                Forms\Components\TextInput::make('kode')
                    ->required(),
                Forms\Components\TextInput::make('gedung'),
                Forms\Components\TextInput::make('lantai'),
                Forms\Components\TextInput::make('lokasi_teks')
                    ->required(),
                Forms\Components\TextInput::make('lat')
                    ->numeric(),
                Forms\Components\TextInput::make('lng')
                    ->numeric(),
                Forms\Components\TextInput::make('radius_m')
                    ->numeric(),
                Forms\Components\Textarea::make('attributes')
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('tgl_expired'),
                Forms\Components\DatePicker::make('terakhir_dicek'),
                Forms\Components\Toggle::make('aktif')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('assetType.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('division.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gedung')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lantai')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lokasi_teks')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lat')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lng')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('radius_m')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tgl_expired')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('terakhir_dicek')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'edit' => Pages\EditAsset::route('/{record}/edit'),
        ];
    }
}
