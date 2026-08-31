<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PermintaanKerjasama extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static \UnitEnum|string|null $navigationGroup = 'Simmagang';

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return 'Permintaan Kerjasama';
    }

    public static function getNavigationLabel(): string
    {
        return 'Permintaan Kerjasama';
    }

    protected static ?int $navigationSort = 9;

    protected string $view = 'filament.pages.permintaan-kerjasama';
}
