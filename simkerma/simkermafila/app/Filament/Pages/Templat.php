<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Templat extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-arrow-down';

    protected string $view = 'filament.pages.templat';
    
    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Templat';
    
    protected static ?string $title = 'Download Templat Dokumen';

}
