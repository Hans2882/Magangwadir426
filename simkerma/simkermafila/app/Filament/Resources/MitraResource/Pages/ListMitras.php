<?php

namespace App\Filament\Resources\MitraResource\Pages;

use App\Filament\Resources\MitraResource;
use App\Models\Mitra;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use \Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListMitras extends ListRecords
{
    protected static string $resource = MitraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Mitra')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'dalam_negeri' => Tab::make('Dalam Negeri')
                ->icon('heroicon-o-building-office-2')
                ->badge(
                    Mitra::whereNull('negara_id')
                        ->orWhere('negara_id', '<', 1)
                        ->count()
                ),

            'luar_negeri' => Tab::make('Luar Negeri')
                ->icon('heroicon-o-globe-alt')
                ->badge(
                    Mitra::where('negara_id', '>=', 1)
                        ->count()
                ),
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
            $query->whereNull('negara_id')
                  ->orWhere('negara_id', '<', 1);
        });
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->recordUrl(fn ($record) => MitraResource::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('nama_mitra')
                    ->label('Nama Mitra')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('kategori.kategori')
    ->label('Kategori IKU')
    ->badge()
    ->searchable()
    ->sortable()
    ->default('-'),

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
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->paginated([10, 25, 50, 100])
            ->filters([
    Tables\Filters\SelectFilter::make('kategori_id')
        ->label('Kategori IKU')
        ->relationship('kategori', 'kategori')
        ->searchable()
        ->preload(),

    Tables\Filters\SelectFilter::make('negara_id')
        ->label('Negara')
        ->relationship('negara', 'nama_negara')
        ->searchable()
        ->preload()
        ->visible(fn ($livewire) => $livewire->activeTab === 'luar_negeri'),
])
            ->defaultSort('nama_mitra', 'asc')
            ->striped();
    }
}