<?php

namespace App\Filament\Resources\MitraResource\Pages;

use App\Filament\Resources\MitraResource;
use App\Models\Mitra;
use App\Filament\Actions\ApiKeyAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use \Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Filters\Filter;

class ListMitras extends ListRecords
{
    protected static string $resource = MitraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ApiKeyAction::make(),
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
                    Mitra::whereRaw('negara_id IS NULL', [], 'and')
                        ->orWhereRaw('negara_id < 1', [], 'or')
                        ->count()
                ),

            'luar_negeri' => Tab::make('Luar Negeri')
                ->icon('heroicon-o-globe-alt')
                ->badge(
                    Mitra::whereRaw('negara_id >= 1', [], 'and')
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

    Tables\Filters\SelectFilter::make('status_kerjasama')
    ->label('Status Kerjasama')
    ->options([
        'none' => 'Belum Ada Kerjasama',
        'active' => 'Aktif',
        'expiring' => 'Akan Berakhir',
        'expired' => 'Berakhir',
    ])
    ->query(function (Builder $query, array $data): Builder {
        $status = $data['value'] ?? null;

        if (!$status) {
            return $query;
        }

        return match ($status) {

            // Tidak memiliki dokumen kerjasama sama sekali
            'none' => $query->whereDoesntHave('kerjasamas'),

            // Memiliki minimal satu kerjasama yang masih AKTIF
            // Sama dengan logika MoU:
            // tanggal_akhir NULL atau > 1 bulan dari sekarang
            'active' => $query->whereHas('kerjasamas', function (Builder $k) {
                $k->where(function (Builder $q) {
                    $q->whereNull('tanggal_akhir')
                        ->orWhereDate(
                            'tanggal_akhir',
                            '>',
                            now()->addMonth()
                        );
                });
            }),

            // Memiliki minimal satu kerjasama yang akan berakhir
            // dalam 1 bulan ke depan
            'expiring' => $query->whereHas('kerjasamas', function (Builder $k) {
                $k->whereNotNull('tanggal_akhir')
                    ->whereDate(
                        'tanggal_akhir',
                        '>=',
                        now()
                    )
                    ->whereDate(
                        'tanggal_akhir',
                        '<=',
                        now()->addMonth()
                    );
            }),

            // Memiliki kerjasama, tetapi tidak memiliki satupun
            // kerjasama yang masih aktif / akan berakhir
            'expired' => $query
                ->whereHas('kerjasamas')
                ->whereDoesntHave('kerjasamas', function (Builder $k) {
                    $k->whereNull('tanggal_akhir')
                        ->orWhereDate(
                            'tanggal_akhir',
                            '>=',
                            now()
                        );
                }),

            default => $query,
        };
    }),

    Tables\Filters\SelectFilter::make('jenis_dokumen')
    ->label('Jenis Dokumen')
    ->multiple()
    ->options([
        1 => 'MoU',
        2 => 'MoA',
        3 => 'PKS',
        4 => 'IA',
        5 => 'SPK',
        6 => 'LoC',
        7 => 'LoI',
    ])
    ->query(function (Builder $query, array $data): Builder {
        $jenisDokumen = $data['values'] ?? [];

        if (empty($jenisDokumen)) {
            return $query;
        }

        return $query->whereHas('kerjasamas', function (Builder $k) use ($jenisDokumen) {
            $k->whereIn('jenis_dokumen_id', $jenisDokumen);
        });
    }),
])
            ->defaultSort('nama_mitra', 'asc')
            ->striped();
    }
}