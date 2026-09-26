<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InspectionResource\Pages;
use App\Filament\Resources\InspectionResource\RelationManagers;
use App\Models\Inspection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InspectionResource extends Resource
{
    protected static ?string $model = Inspection::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('asset_id')
                    ->relationship('asset', 'id')
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\DateTimePicker::make('inspected_at')
                    ->required(),
                Forms\Components\TextInput::make('gps_lat')
                    ->numeric(),
                Forms\Components\TextInput::make('gps_lng')
                    ->numeric(),
                Forms\Components\TextInput::make('gps_accuracy')
                    ->numeric(),
                Forms\Components\TextInput::make('jarak_m')
                    ->numeric(),
                Forms\Components\Toggle::make('qr_verified')
                    ->required(),
                Forms\Components\TextInput::make('gate_status')
                    ->required(),
                Forms\Components\Textarea::make('catatan')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('rekomendasi')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('status')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('asset.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('inspected_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gps_lat')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gps_lng')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gps_accuracy')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jarak_m')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('qr_verified')
                    ->boolean(),
                Tables\Columns\TextColumn::make('gate_status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
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
            'index' => Pages\ListInspections::route('/'),
            'create' => Pages\CreateInspection::route('/create'),
            'edit' => Pages\EditInspection::route('/{record}/edit'),
        ];
    }
}
