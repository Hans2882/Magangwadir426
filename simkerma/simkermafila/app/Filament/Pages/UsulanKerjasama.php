<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Models\Mitra;

class UsulanKerjasama extends Page implements HasTable
{
    use InteractsWithTable;
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

    public function table(Table $table): Table
    {
        return $table
            ->query(Mitra::query()->where('id', -1)) // Dummy query for empty table
            ->columns([
                TextColumn::make('nama_mitra')
                    ->label('Nama Mitra'),
                TextColumn::make('bentuk_kerjasama')
                    ->label('Bentuk Kerjasama'),
                TextColumn::make('pengusul')
                    ->label('Pengusul'),
            ])
            ->emptyStateHeading('Belum ada usulan kerjasama');
    }
}
