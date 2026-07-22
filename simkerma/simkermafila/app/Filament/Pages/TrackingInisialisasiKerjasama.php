<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class TrackingInisialisasiKerjasama extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static \UnitEnum|string|null $navigationGroup = 'Data Kerjasama';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.tracking-inisialisasi-kerjasama';
}
