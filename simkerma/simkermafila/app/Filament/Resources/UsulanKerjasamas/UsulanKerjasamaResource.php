<?php

namespace App\Filament\Resources\UsulanKerjasamas;

use App\Filament\Resources\UsulanKerjasamas\Pages\CreateUsulanKerjasama;
use App\Filament\Resources\UsulanKerjasamas\Pages\EditUsulanKerjasama;
use App\Filament\Resources\UsulanKerjasamas\Pages\ListUsulanKerjasamas;
use App\Filament\Resources\UsulanKerjasamas\Schemas\UsulanKerjasamaForm;
use App\Filament\Resources\UsulanKerjasamas\Tables\UsulanKerjasamasTable;
use App\Models\UsulanKerjasama;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UsulanKerjasamaResource extends Resource
{
    protected static ?string $model = UsulanKerjasama::class;

    protected static ?string $navigationLabel = 'Inisiasi Kerjasama';
    
    protected static ?string $pluralModelLabel = 'Inisiasi Kerjasama';
    
    protected static ?string $modelLabel = 'Inisiasi Kerjasama';

    protected static \UnitEnum|string|null $navigationGroup = 'Inisiasi Kerjasama';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return UsulanKerjasamaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsulanKerjasamasTable::configure($table);
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
            'index' => ListUsulanKerjasamas::route('/'),
            'create' => CreateUsulanKerjasama::route('/create'),
            'edit' => EditUsulanKerjasama::route('/{record}/edit'),
        ];
    }
}
