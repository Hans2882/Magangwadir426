<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KuisionerKepuasanResource\Pages;
use App\Models\KuisionerKepuasan;
use Filament\Resources\Resource;

class KuisionerKepuasanResource extends Resource
{
    protected static ?string $model = KuisionerKepuasan::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static \UnitEnum|string|null $navigationGroup = 'Pelaporan & Tracking';

    protected static ?string $navigationLabel = 'Kuisioner Kepuasan';

    protected static ?string $modelLabel = 'Kuisioner Kepuasan';

    protected static ?string $pluralModelLabel = 'Kuisioner Kepuasan';

    protected static ?string $slug = 'kuisioner-kepuasan';

    protected static ?int $navigationSort = 8;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKuisionerKepuasan::route('/'),
        ];
    }
}
