<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class UsulanKerjasama extends Page
{
    protected string $view = 'filament.pages.usulan-kerjasama';
    
    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Usulan Kerjasama';
    }

    public function getTitle(): string 
    {
        return 'Usulan Kerjasama';
    }
}
