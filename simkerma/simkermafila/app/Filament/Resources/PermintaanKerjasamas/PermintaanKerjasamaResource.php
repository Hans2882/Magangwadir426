<?php

namespace App\Filament\Resources\PermintaanKerjasamas;

use App\Filament\Resources\PermintaanKerjasamas\Pages;
use App\Filament\Resources\PermintaanKerjasamas\Schemas\PermintaanKerjasamaForm;
use App\Filament\Resources\PermintaanKerjasamas\Tables\PermintaanKerjasamasTable;
use App\Models\PermintaanKerjasama;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class PermintaanKerjasamaResource extends Resource
{
    protected static ?string $model = PermintaanKerjasama::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static \UnitEnum|string|null $navigationGroup = 'Simmagang';
    protected static ?string $navigationLabel = 'Permintaan Kerjasama';

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return PermintaanKerjasamaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PermintaanKerjasamasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermintaanKerjasamas::route('/'),
            'create' => Pages\CreatePermintaanKerjasama::route('/create'),
            'edit' => Pages\EditPermintaanKerjasama::route('/{record}/edit'),
        ];
    }
}
