<?php

namespace App\Filament\Resources\MasterKotas;

use App\Filament\Resources\MasterKotas\Pages\ManageMasterKotas;
use App\Models\MasterKota;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MasterKotaResource extends Resource
{
    protected static ?string $model = MasterKota::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Master Kota';
    protected static ?string $modelLabel = 'Kota';
    protected static ?string $pluralModelLabel = 'Master Kota';
    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user?->userPrivilege?->privilege?->is_admin_panel ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('provinsi_id')
                    ->label('Provinsi')
                    ->relationship('provinsi', 'nama_provinsi')
                    ->searchable()
                    ->preload()
                    ->required(),
                \Filament\Forms\Components\TextInput::make('nama_kota')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('nama_kota')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('provinsi.nama_provinsi')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMasterKotas::route('/'),
        ];
    }
}
