<?php

namespace App\Filament\Resources\PermintaanKerjasamas;

use App\Filament\Resources\PermintaanKerjasamas\Pages;
use App\Filament\Resources\PermintaanKerjasamas\Schemas\PermintaanKerjasamaForm;
use App\Filament\Resources\PermintaanKerjasamas\Tables\PermintaanKerjasamasTable;
use App\Models\PermintaanKerjasama;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PermintaanKerjasamaResource extends Resource
{
    protected static ?string $model = PermintaanKerjasama::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static \UnitEnum|string|null $navigationGroup = 'Simmagang';
    protected static ?string $navigationLabel = 'Permintaan Kerjasama';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user?->isMahasiswa()) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canView(Model $record): bool
    {
        $user = Auth::user();
        if ($user?->isMahasiswa()) {
            return (int) $record->user_id === (int) $user->id;
        }
        return true;
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user?->isMahasiswa()) {
            return false;
        }
        return true;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user?->isMahasiswa()) {
            return false;
        }
        return true;
    }

    public static function canDeleteAny(): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user?->isMahasiswa()) {
            return false;
        }
        return true;
    }

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
