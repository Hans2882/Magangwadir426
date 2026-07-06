<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Models\MasterMitraIku;

class KategoriMitra extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Data Mitra';

    protected static ?string $navigationLabel = 'Kategori Mitra';

    protected static ?string $title = 'Kategori Mitra';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.kategori-mitra';

    public function table(Table $table): Table
    {
        return $table
            ->query(MasterMitraIku::query())
            ->columns([
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bobot')
                    ->label('Bobot')
                    ->sortable(),
            ]);
    }
}
