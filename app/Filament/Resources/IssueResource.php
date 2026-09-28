<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HanyaAdminBolehMenulis;
use App\Filament\Resources\IssueResource\Pages;
use App\Filament\Resources\IssueResource\RelationManagers;
use App\Models\Issue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IssueResource extends Resource
{
    use HanyaAdminBolehMenulis;

    protected static ?string $model = Issue::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('asset_id')
                    ->relationship('asset', 'id')
                    ->required(),
                Forms\Components\Select::make('inspection_id')
                    ->relationship('inspection', 'id'),
                Forms\Components\Select::make('checklist_item_id')
                    ->relationship('checklistItem', 'id'),
                Forms\Components\TextInput::make('item')
                    ->required(),
                Forms\Components\TextInput::make('severity')
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Textarea::make('tindak_lanjut')
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('target_selesai'),
                Forms\Components\DateTimePicker::make('selesai_pada'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('asset.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('inspection.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('checklistItem.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item')
                    ->searchable(),
                Tables\Columns\TextColumn::make('severity')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('target_selesai')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('selesai_pada')
                    ->dateTime()
                    ->sortable(),
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
            'index' => Pages\ListIssues::route('/'),
            'create' => Pages\CreateIssue::route('/create'),
            'edit' => Pages\EditIssue::route('/{record}/edit'),
        ];
    }
}
