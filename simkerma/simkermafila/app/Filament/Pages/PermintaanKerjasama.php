<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PermintaanKerjasama extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static \UnitEnum|string|null $navigationGroup = 'Simmagang';

    protected static ?string $navigationLabel = 'Permintaan Kerjasama';

    protected static ?string $title = 'Permintaan Kerjasama';

    protected static ?int $navigationSort = 9;

    protected string $view = 'filament.pages.permintaan-kerjasama';
}
