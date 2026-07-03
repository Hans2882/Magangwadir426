<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PermintaanKerjasama extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Simmagang';

    protected static ?string $navigationLabel = 'Permintaan Kerjasama';

    protected static ?string $title = 'Permintaan Kerjasama';

    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.permintaan-kerjasama';
}
