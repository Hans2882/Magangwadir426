<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrivilegeResource\Pages;
use App\Filament\Resources\PrivilegeResource\RelationManagers;
use App\Models\Privilege;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PrivilegeResource extends Resource
{
    protected static ?string $model = Privilege::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 3;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama')
                    ->required()
                    ->maxLength(100),
                Forms\Components\Textarea::make('deskripsi')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('can_create')
                    ->required(),
                Forms\Components\Toggle::make('can_read')
                    ->required(),
                Forms\Components\Toggle::make('can_update')
                    ->required(),
                Forms\Components\Toggle::make('can_delete')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->searchable(),
                Tables\Columns\IconColumn::make('can_create')
                    ->boolean(),
                Tables\Columns\IconColumn::make('can_read')
                    ->boolean(),
                Tables\Columns\IconColumn::make('can_update')
                    ->boolean(),
                Tables\Columns\IconColumn::make('can_delete')
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
            'index' => Pages\ListPrivileges::route('/'),
            'create' => Pages\CreatePrivilege::route('/create'),
            'edit' => Pages\EditPrivilege::route('/{record}/edit'),
        ];
    }
}
