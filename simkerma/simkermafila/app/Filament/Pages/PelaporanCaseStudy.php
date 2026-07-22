<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PelaporanCaseStudy extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'Data Kerjasama';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.pelaporan-case-study';
}
