<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class KategoriMitra extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Data Mitra';

    protected static ?string $navigationLabel = 'Kategori Mitra';

    protected static ?string $title = 'Kategori Mitra';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.kategori-mitra';
}
