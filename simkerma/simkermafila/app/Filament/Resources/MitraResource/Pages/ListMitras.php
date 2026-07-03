<?php

namespace App\Filament\Resources\MitraResource\Pages;

use App\Filament\Resources\MitraResource;
use App\Models\Mitra;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ListMitras extends ListRecords
{
    protected static string $resource = MitraResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'dalam_negeri' => Tab::make('Dalam Negeri')
                ->icon('heroicon-o-building-office-2')
                ->badge(Mitra::whereNull('negara_id')->orWhere('negara_id', '<', 1)->count()),
            'luar_negeri' => Tab::make('Luar Negeri')
                ->icon('heroicon-o-globe-alt')
                ->badge(Mitra::where('negara_id', '>=', 1)->count()),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'dalam_negeri';
    }

    protected function getTableQuery(): Builder
    {
        if ($this->activeTab === 'luar_negeri') {
            return Mitra::query()->where('negara_id', '>=', 1);
        }
        return Mitra::query()->where(function ($query) {
            $query->whereNull('negara_id')->orWhere('negara_id', '<', 1);
        });
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_mitra')
                    ->label('Nama Mitra')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('negara.nama_negara')
                    ->label('Negara')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->visible(fn ($livewire) => $livewire->activeTab === 'luar_negeri'),
                Tables\Columns\TextColumn::make('telepon')
                    ->label('No. Telepon')
                    ->default('-')
                    ->visible(fn ($livewire) => $livewire->activeTab !== 'luar_negeri'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->default('-')
                    ->visible(fn ($livewire) => $livewire->activeTab !== 'luar_negeri'),
                Tables\Columns\TextColumn::make('alamat')
                    ->label('Alamat')
                    ->limit(40)
                    ->default('-')
                    ->visible(fn ($livewire) => $livewire->activeTab !== 'luar_negeri'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('negara')
                    ->label('Negara')
                    ->relationship('negara', 'nama_negara')
                    ->visible(fn ($livewire) => $livewire->activeTab === 'luar_negeri'),
            ])
            ->defaultSort('nama_mitra', 'asc')
            ->striped();
    }
}
