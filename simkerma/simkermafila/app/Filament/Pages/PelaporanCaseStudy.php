<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Models\Kerjasama;

use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class PelaporanCaseStudy extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'Pelaporan & Tracking';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.pelaporan-case-study';

    public function table(Table $table): Table
    {
        return $table
            ->query(Kerjasama::query()->where('id', -1)) // Empty placeholder query
            ->columns([
                TextColumn::make('judul')->label('Judul')->searchable()->limit(50),
                TextColumn::make('mitra.nama_mitra')->label('Nama Mitra')->searchable()->limit(30),
                TextColumn::make('negara')
                    ->label('Negara')
                    ->getStateUsing(function ($record) {
                        return $record->jenis === 'Luar Negeri'
                            ? ($record->mitra?->negara?->nama_negara ?? '-')
                            : 'Indonesia';
                    })
                    ->searchable(),
                TextColumn::make('jenisDokumen.nama')->label('Jenis Kegiatan')->badge(),
            ]);
    }

}
