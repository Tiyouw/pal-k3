<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChecklistItemResource\Pages;
use App\Filament\Resources\ChecklistItemResource\RelationManagers;
use App\Models\ChecklistItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChecklistItemResource extends Resource
{
    protected static ?string $model = ChecklistItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('checklist_group_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('label')
                    ->required(),
                Forms\Components\TextInput::make('answer_type')
                    ->required(),
                Forms\Components\TextInput::make('jumlah_baku')
                    ->numeric(),
                Forms\Components\TextInput::make('satuan'),
                Forms\Components\TextInput::make('dasar_hukum'),
                Forms\Components\TextInput::make('severity_default')
                    ->required(),
                Forms\Components\Toggle::make('wajib')
                    ->required(),
                Forms\Components\TextInput::make('urut')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('aktif')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('checklist_group_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('label')
                    ->searchable(),
                Tables\Columns\TextColumn::make('answer_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jumlah_baku')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('satuan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dasar_hukum')
                    ->searchable(),
                Tables\Columns\TextColumn::make('severity_default')
                    ->searchable(),
                Tables\Columns\IconColumn::make('wajib')
                    ->boolean(),
                Tables\Columns\TextColumn::make('urut')
                    ->numeric()
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
            'index' => Pages\ListChecklistItems::route('/'),
            'create' => Pages\CreateChecklistItem::route('/create'),
            'edit' => Pages\EditChecklistItem::route('/{record}/edit'),
        ];
    }
}
