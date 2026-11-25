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
                'value' => $tableFilters['negara']['value'] ?? $tableFilters['negara_id']['value'] ?? null,
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

    protected function getFilteredBaseQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();
        $this->applyFiltersToTableQuery($query);
        $this->applySearchToTableQuery($query);

        return $query;
    }

    public function getTabs(): array
    {
        return [
            'dalam_negeri' => Tab::make('Dalam Negeri')
                ->icon('heroicon-o-building-office-2')
                ->badge(fn () => $this->getFilteredBaseQuery()
                    ->where(function ($query) {
                        $query->whereNull('negara_id')
                            ->orWhere('negara_id', '<', 1);
                    })
                    ->count()
                ),

            'luar_negeri' => Tab::make('Luar Negeri')
                ->icon('heroicon-o-globe-alt')
                ->badge(fn () => $this->getFilteredBaseQuery()
                    ->where('negara_id', '>=', 1)
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
                    ->label('Kategori Perusahaan')
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
                | KATEGORI PERUSAHAAN
                |--------------------------------------------------------------------------
                */
                Tables\Filters\SelectFilter::make('kategori_id')
                    ->label('Kategori Perusahaan')
                    ->relationship('kategori', 'kategori')
                    ->searchable()
                    ->preload(),

                /*
                |--------------------------------------------------------------------------
                | NEGARA
                |--------------------------------------------------------------------------
                */
                Tables\Filters\SelectFilter::make('negara')
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

                /*
                |--------------------------------------------------------------------------
                | JURUSAN & PROGRAM STUDI (BERDASARKAN PKS / IA / MoA / SPK / LoC)
                |--------------------------------------------------------------------------
                */
                Tables\Filters\Filter::make('jurusan_prodi')
                    ->label('Jurusan & Program Studi')
                    ->form([
                        \Filament\Forms\Components\Select::make('jurusans')
                            ->label('Jurusan')
                            ->options(\App\Models\MasterJurusan::pluck('nama_jurusan', 'id'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('prodis', [])),

                        \Filament\Forms\Components\Select::make('prodis')
                            ->label('Program Studi')
                            ->options(function (callable $get) {
                                $jurusans = $get('jurusans');
                                if (! empty($jurusans)) {
                                    return \App\Models\MasterProgramStudi::whereIn('jurusan_id', $jurusans)
                                        ->pluck('nama_prodi', 'id');
                                }

                                return \App\Models\MasterProgramStudi::pluck('nama_prodi', 'id');
                            })
                            ->multiple()
                            ->searchable()
                            ->preload(),

                        \Filament\Forms\Components\Checkbox::make('sertakan_mou')
                            ->label('Sertakan mitra yang memiliki MoU')
                            ->helperText('MoU berlaku tingkat institusi sehingga tetap ditampilkan meskipun tidak terikat ke jurusan/prodi tertentu.')
                            ->default(true),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $jurusans = array_values(array_filter((array) ($data['jurusans'] ?? [])));
                        $prodis = array_values(array_filter((array) ($data['prodis'] ?? [])));
                        $sertakanMou = (bool) ($data['sertakan_mou'] ?? true);

                        if (empty($jurusans) && empty($prodis)) {
                            return $query;
                        }

                        return $query->where(function (Builder $outer) use ($jurusans, $prodis, $sertakanMou) {
                            $outer->whereHas('kerjasamas', function (Builder $k) use ($jurusans, $prodis) {
                                $k->whereIn('jenis_dokumen_id', [2, 3, 4, 5, 6])
                                    ->when(! empty($jurusans), function (Builder $jq) use ($jurusans) {
                                        $jq->where(function (Builder $sub) use ($jurusans) {
                                            $sub->whereHas('jurusans', fn ($q) => $q->whereIn('master_jurusans.id', $jurusans))
                                                ->orWhereHas('prodis', fn ($q) => $q->whereIn('master_program_studi.jurusan_id', $jurusans));
                                        });
                                    })
                                    ->when(! empty($prodis), function (Builder $pq) use ($prodis) {
                                        $pq->whereHas('prodis', fn ($q) => $q->whereIn('master_program_studi.id', $prodis));
                                    });
                            });

                            if ($sertakanMou) {
                                $outer->orWhereHas('kerjasamas', function (Builder $mou) {
                                    $mou->whereIn('jenis_dokumen_id', [1, 7]);
                                });
                            }
                        });
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        $jurusans = array_values(array_filter((array) ($data['jurusans'] ?? [])));
                        $prodis = array_values(array_filter((array) ($data['prodis'] ?? [])));

                        if (empty($jurusans) && empty($prodis)) {
                            return [];
                        }

                        if (! empty($jurusans)) {
                            $names = \App\Models\MasterJurusan::whereIn('id', $jurusans)->pluck('nama_jurusan')->implode(', ');
                            $indicators[] = 'Jurusan: ' . $names;
                        }

                        if (! empty($prodis)) {
                            $names = \App\Models\MasterProgramStudi::whereIn('id', $prodis)->pluck('nama_prodi')->implode(', ');
                            $indicators[] = 'Prodi: ' . $names;
                        }

                        if ($data['sertakan_mou'] ?? true) {
                            $indicators[] = 'Termasuk Mitra ber-MoU';
                        }

                        return $indicators;
                    }),
            ])

            ->defaultSort('nama_mitra', 'asc')
            ->striped();
    }
}