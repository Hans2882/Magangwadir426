<?php

namespace App\Filament\Resources\MitraResource\Pages;

use App\Filament\Resources\MitraResource;
use App\Models\Mitra;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListMitras extends ListRecords
{
    protected static string $resource = MitraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('api')
                ->label('Lihat API')
                ->icon('heroicon-o-code-bracket')
                ->modalHeading('API Data Mitra')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(function () {
    $tableFilters = $this->tableFilters ?? [];

    return view('api.mitra', [
        'filters' => [
            'kategori_id' => [
                'value' => $tableFilters['kategori_id']['value'] ?? null,
            ],

            'negara_id' => [
                'value' => $tableFilters['negara_id']['value'] ?? null,
            ],

            'status_kerjasama' => [
                'value' => $tableFilters['status_kerjasama']['value'] ?? null,
            ],

            'jenis_dokumen' => [
                'values' => $tableFilters['jenis_dokumen']['values'] ?? [],
            ],
        ],

        'activeTab' => $this->activeTab ?? 'dalam_negeri',
    ]);
}),

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
                    Mitra::where(function ($query) {
                        $query->whereNull('negara_id')
                            ->orWhere('negara_id', '<', 1);
                    })->count()
                ),

            'luar_negeri' => Tab::make('Luar Negeri')
                ->icon('heroicon-o-globe-alt')
                ->badge(
                    Mitra::where('negara_id', '>=', 1)->count()
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
            return Mitra::query()
                ->where('negara_id', '>=', 1);
        }

        return Mitra::query()
            ->where(function ($query) {
                $query->whereNull('negara_id')
                    ->orWhere('negara_id', '<', 1);
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->recordUrl(
                fn ($record) => MitraResource::getUrl('view', [
                    'record' => $record,
                ])
            )

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
                    ->visible(
                        fn ($livewire) =>
                            $livewire->activeTab === 'luar_negeri'
                    ),

                Tables\Columns\TextColumn::make('telepon')
                    ->label('No. Telepon')
                    ->default('-')
                    ->visible(
                        fn ($livewire) =>
                            $livewire->activeTab !== 'luar_negeri'
                    ),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->default('-')
                    ->visible(
                        fn ($livewire) =>
                            $livewire->activeTab !== 'luar_negeri'
                    ),

                Tables\Columns\TextColumn::make('alamat')
                    ->label('Alamat')
                    ->limit(40)
                    ->default('-')
                    ->visible(
                        fn ($livewire) =>
                            $livewire->activeTab !== 'luar_negeri'
                    ),
            ])

            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])

            ->paginated([10, 25, 50, 100])

            ->filters([
                Tables\Filters\SelectFilter::make('status_mitra')
                    ->label('Status')
                    ->options([
                        'AKTIF' => 'Aktif',
                        'AKAN BERAKHIR' => 'Akan Berakhir',
                        'BERAKHIR' => 'Berakhir',
                    ])
                    ->default('AKTIF')
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['value'] ?? null;

                        if (!$status) {
                            return $query;
                        }

                        return match ($status) {
                            'AKTIF' => $query->whereHas(
                                'kerjasamas',
                                function (Builder $mouQuery) {
                                    $mouQuery->where('jenis_dokumen_id', 1)
                                        ->where(function (Builder $dateQuery) {
                                            $dateQuery->whereNull('tanggal_akhir')
                                                ->orWhereDate('tanggal_akhir', '>', now()->addMonth());
                                        });
                                }
                            ),

                            'AKAN BERAKHIR' => $query->whereHas(
                                'kerjasamas',
                                function (Builder $mouQuery) {
                                    $mouQuery->where('jenis_dokumen_id', 1)
                                        ->whereNotNull('tanggal_akhir')
                                        ->whereDate('tanggal_akhir', '>=', now())
                                        ->whereDate('tanggal_akhir', '<=', now()->addMonth());
                                }
                            ),

                            'BERAKHIR' => $query->whereHas(
                                'kerjasamas',
                                function (Builder $mouQuery) {
                                    $mouQuery->where('jenis_dokumen_id', 1)
                                        ->whereDate('tanggal_akhir', '<', now());
                                }
                            ),

                            default => $query,
                        };
                    }),

                /*
                |--------------------------------------------------------------------------
                | KATEGORI IKU
                |--------------------------------------------------------------------------
                */
                Tables\Filters\SelectFilter::make('kategori_id')
                    ->label('Kategori IKU')
                    ->relationship('kategori', 'kategori')
                    ->searchable()
                    ->preload(),

                /*
                |--------------------------------------------------------------------------
                | NEGARA
                |--------------------------------------------------------------------------
                */
                Tables\Filters\SelectFilter::make('negara_id')
                    ->label('Negara')
                    ->relationship('negara', 'nama_negara')
                    ->searchable()
                    ->preload()
                    ->visible(
                        fn ($livewire) =>
                            $livewire->activeTab === 'luar_negeri'
                    ),

                /*
                |--------------------------------------------------------------------------
                | STATUS KERJASAMA
                |--------------------------------------------------------------------------
                */
                Tables\Filters\SelectFilter::make('status_kerjasama')
                    ->label('Status Kerjasama')
                    ->options([
                        'none' => 'Belum Ada Kerjasama',
                        'active' => 'Aktif',
                        'expiring' => 'Akan Berakhir',
                        'expired' => 'Berakhir',
                    ])
                    ->query(function (
                        Builder $query,
                        array $data
                    ): Builder {
                        $status = $data['value'] ?? null;

                        if (!$status) {
                            return $query;
                        }

                        return match ($status) {

                            'none' => $query->whereDoesntHave(
                                'kerjasamas'
                            ),

                            'active' => $query->whereHas(
                                'kerjasamas',
                                function (Builder $k) {
                                    $k->where(function (Builder $q) {
                                        $q->whereNull('tanggal_akhir')
                                            ->orWhereDate(
                                                'tanggal_akhir',
                                                '>',
                                                now()->addMonth()
                                            );
                                    });
                                }
                            ),

                            'expiring' => $query->whereHas(
                                'kerjasamas',
                                function (Builder $k) {
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
                                }
                            ),

                            'expired' => $query
                                ->whereHas('kerjasamas')
                                ->whereDoesntHave(
                                    'kerjasamas',
                                    function (Builder $k) {
                                        $k->whereNull('tanggal_akhir')
                                            ->orWhereDate(
                                                'tanggal_akhir',
                                                '>=',
                                                now()
                                            );
                                    }
                                ),

                            default => $query,
                        };
                    }),

                /*
                |--------------------------------------------------------------------------
                | JENIS DOKUMEN
                |--------------------------------------------------------------------------
                */
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
                    ->query(function (
                        Builder $query,
                        array $data
                    ): Builder {
                        $jenisDokumen = $data['values'] ?? [];

                        if (empty($jenisDokumen)) {
                            return $query;
                        }

                        return $query->whereHas(
                            'kerjasamas',
                            function (Builder $k) use ($jenisDokumen) {
                                $k->whereIn(
                                    'jenis_dokumen_id',
                                    $jenisDokumen
                                );
                            }
                        );
                    }),
            ])

            ->defaultSort('nama_mitra', 'asc')
            ->striped();
    }
}