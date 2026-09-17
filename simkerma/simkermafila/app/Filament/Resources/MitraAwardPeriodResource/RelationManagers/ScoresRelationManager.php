<?php

namespace App\Filament\Resources\MitraAwardPeriodResource\RelationManagers;

use App\Models\Mitra;
use App\Models\MitraAwardScore;
use App\Services\MitraAwardCalculator;
use App\Exports\MitraAwardScoreExport;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

class ScoresRelationManager extends RelationManager
{
    protected static string $relationship = 'scores';

    protected static ?string $title = 'Peserta dan Penilaian';

    public function isReadOnly(): bool
    {
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | FORM
    |--------------------------------------------------------------------------
    */

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Wizard::make([
                /*
                |--------------------------------------------------------------------------
                | STEP 1 — MITRA
                |--------------------------------------------------------------------------
                */
                Step::make('Mitra')
                    ->icon('heroicon-o-building-office-2')
                    ->description('Pilih mitra yang akan dinilai.')
                    ->schema([
                        Section::make('Identitas Mitra')
                            ->description(
                                'Setiap mitra hanya dapat dimasukkan satu kali pada periode award ini.'
                            )
                            ->schema([
                                Forms\Components\Select::make('mitra_id')
                                    ->label('Mitra')
                                    ->placeholder('Pilih atau cari nama mitra...')
                                    ->relationship('mitra', 'nama_mitra')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->rule(
                                        fn (?MitraAwardScore $record) => Rule::unique(
                                            'mitra_award_scores',
                                            'mitra_id'
                                        )
                                            ->where(
                                                fn ($query) => $query->where(
                                                    'mitra_award_period_id',
                                                    $this->ownerRecord->getKey()
                                                )
                                            )
                                            ->ignore($record?->getKey())
                                    )
                                    ->afterStateUpdated(
                                        function ($state, Set $set): void {
                                            $set(
                                                'dokumen_score',
                                                $this->documentScore($state)
                                            );
                                        }
                                    )
                                    ->helperText('Cari berdasarkan nama mitra.')
                                    ->columnSpanFull(),

                                Grid::make([
                                    'default' => 1,
                                    'md' => 3,
                                ])
                                    ->schema([
                                        Forms\Components\Placeholder::make(
                                            'selected_category'
                                        )
                                            ->label('Kategori IKU')
                                            ->content(
                                                fn (Get $get): string => $this->mitraCategory(
                                                    $get('mitra_id')
                                                )
                                            ),

                                        Forms\Components\Placeholder::make(
                                            'selected_country'
                                        )
                                            ->label('Negara')
                                            ->content(
                                                fn (Get $get): string => $this->mitraCountry(
                                                    $get('mitra_id')
                                                )
                                            ),

                                        Forms\Components\Placeholder::make(
                                            'selected_document_score'
                                        )
                                            ->label('Skor Dokumen')
                                            ->content(
                                                fn (Get $get): string => $this->documentScore(
                                                    $get('mitra_id')
                                                ) . ' / 4'
                                            ),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->compact()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | STEP 2 — DOKUMEN
                |--------------------------------------------------------------------------
                */
                Step::make('Dokumen')
                    ->icon('heroicon-o-document-check')
                    ->description('Dokumen kerja sama dideteksi otomatis.')
                    ->schema([
                        Section::make('Dokumen Kerja Sama')
                            ->description(
                                'Skor dokumen dihitung otomatis dari dokumen terbaik yang dimiliki mitra.'
                            )
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 2,
                                ])
                                    ->schema([
                                        Forms\Components\Placeholder::make(
                                            'document_level'
                                        )
                                            ->label('Jenis / Level Dokumen')
                                            ->content(
                                                fn (Get $get): string => $this->documentLevel(
                                                    $get('mitra_id')
                                                )
                                            ),

                                        Forms\Components\Placeholder::make(
                                            'document_score_display'
                                        )
                                            ->label('Skor Dokumen')
                                            ->content(
                                                fn (Get $get): string => $this->documentScore(
                                                    $get('mitra_id')
                                                ) . ' / 4'
                                            ),
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\Placeholder::make(
                                    'document_explanation'
                                )
                                    ->label('Keterangan')
                                    ->content(
                                        fn (Get $get): string => $this->documentExplanation(
                                            $get('mitra_id')
                                        )
                                    )
                                    ->columnSpanFull(),

                                Forms\Components\Hidden::make('dokumen_score')
                                    ->default(0)
                                    ->dehydrated(true),
                            ])
                            ->columns(1)
                            ->compact()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | STEP 3 — AKADEMIK
                |--------------------------------------------------------------------------
                */
                Step::make('Akademik')
                    ->icon('heroicon-o-academic-cap')
                    ->description('Isi aktivitas kemitraan bidang akademik.')
                    ->schema([
                        Section::make('Kemitraan Akademik')
                            ->description(
                                'Masukkan jumlah aktivitas aktual.'
                            )
                            ->schema([
                                $this->countField(
                                    'kurikulum',
                                    'Kurikulum',
                                    'Jumlah workshop/kegiatan kurikulum.'
                                ),

                                $this->countField(
                                    'magang',
                                    'Magang',
                                    'Jumlah mahasiswa yang mengikuti kegiatan magang.'
                                ),

                                $this->countField(
                                    'dosen_industri',
                                    'Dosen Industri',
                                    'Jumlah dosen yang terlibat.'
                                ),

                                $this->countField(
                                    'rekrutmen',
                                    'Rekrutmen',
                                    'Jumlah alumni yang direkrut.'
                                ),
                            ])
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                            ])
                            ->compact()
                            ->columnSpanFull(),

                        $this->scaleGuideSection([
                            'kurikulum',
                            'magang',
                            'dosen_industri',
                            'rekrutmen',
                        ]),
                    ])
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | STEP 4 — PENELITIAN & PKM
                |--------------------------------------------------------------------------
                */
                Step::make('Penelitian & PkM')
                    ->icon('heroicon-o-beaker')
                    ->description('Isi aktivitas penelitian dan pengabdian.')
                    ->schema([
                        Section::make('Penelitian')
                            ->description(
                                'Untuk nilai uang, masukkan nominal aktual dalam Rupiah.'
                            )
                            ->schema([
                                $this->moneyField(
                                    'penelitian_cash',
                                    'Penelitian In-cash',
                                    'Kontribusi penelitian dalam bentuk uang.'
                                ),

                                $this->moneyField(
                                    'penelitian_kind',
                                    'Penelitian In-kind',
                                    'Nilai setara Rupiah barang/jasa.'
                                ),

                                $this->countField(
                                    'hilirisasi',
                                    'Hilirisasi',
                                    'Jumlah produk/jasa yang dihilirisasi.'
                                ),
                            ])
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                            ])
                            ->compact()
                            ->columnSpanFull(),

                        Section::make('Pengabdian kepada Masyarakat')
                            ->schema([
                                $this->countField(
                                    'khalayak_pkm',
                                    'Khalayak Sasaran PkM',
                                    'Jumlah masyarakat sasaran.'
                                ),

                                $this->countField(
                                    'publikasi_bersama',
                                    'Publikasi Bersama',
                                    'Jumlah artikel publikasi bersama.'
                                ),

                                $this->countField(
                                    'co_hosting',
                                    'Co-hosting',
                                    'Jumlah pertemuan ilmiah bersama.'
                                ),
                            ])
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                            ])
                            ->compact()
                            ->columnSpanFull(),

                        $this->scaleGuideSection([
                            'penelitian_cash',
                            'penelitian_kind',
                            'hilirisasi',
                            'khalayak_pkm',
                            'publikasi_bersama',
                            'co_hosting',
                        ]),
                    ])
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | STEP 5 — INCOME GENERATION
                |--------------------------------------------------------------------------
                */
                Step::make('Income')
                    ->icon('heroicon-o-banknotes')
                    ->description('Isi aktivitas income generation.')
                    ->schema([
                        Section::make('Income Generation')
                            ->description(
                                'Masukkan jumlah atau nominal aktual sesuai satuan indikator.'
                            )
                            ->schema([
                                $this->countField(
                                    'pelatihan_sertifikasi',
                                    'Pelatihan / Sertifikasi',
                                    'Jumlah peserta/proyek.'
                                ),

                                $this->moneyField(
                                    'kajian_tenaga_ahli',
                                    'Kajian / Tenaga Ahli',
                                    'Nilai proyek dalam Rupiah.'
                                ),

                                $this->moneyField(
                                    'hibah_alat',
                                    'Hibah Alat / Sarana / Beasiswa',
                                    'Nilai setara Rupiah hibah.'
                                ),
                            ])
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                            ])
                            ->compact()
                            ->columnSpanFull(),

                        $this->scaleGuideSection([
                            'pelatihan_sertifikasi',
                            'kajian_tenaga_ahli',
                            'hibah_alat',
                        ]),
                    ])
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | STEP 6 — NILAI TAMBAH
                |--------------------------------------------------------------------------
                */
                Step::make('Nilai Tambah')
                    ->icon('heroicon-o-star')
                    ->description('Nilai reputasi dan perluasan jejaring.')
                    ->schema([
                        Section::make('Nilai Tambah')
                            ->description(
                                'Pilih tingkat penilaian berdasarkan kondisi mitra.'
                            )
                            ->schema([
                                $this->likertField(
                                    'reputasi',
                                    'Reputasi',
                                    'Tingkat reputasi mitra.'
                                ),

                                $this->likertField(
                                    'perluasan_jejaring',
                                    'Perluasan Jejaring',
                                    'Dampak terhadap perluasan jejaring.'
                                ),
                            ])
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                            ])
                            ->compact()
                            ->columnSpanFull(),

                        $this->scaleGuideSection([
                            'reputasi',
                            'perluasan_jejaring',
                        ]),
                    ])
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | STEP 7 — REVIEW
                |--------------------------------------------------------------------------
                */
                Step::make('Review')
                    ->icon('heroicon-o-check-circle')
                    ->description('Periksa seluruh nilai sebelum disimpan.')
                    ->schema([
                        Section::make('Ringkasan Penilaian')
                            ->description(
                                'Final Score dihitung otomatis menggunakan konfigurasi Skala & Bobot periode ini.'
                            )
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 3,
                                ])
                                    ->schema([
                                        Forms\Components\Placeholder::make(
                                            'review_mitra'
                                        )
                                            ->label('Mitra')
                                            ->content(
                                                fn (Get $get): string => $this->mitraName(
                                                    $get('mitra_id')
                                                )
                                            ),

                                        Forms\Components\Placeholder::make(
                                            'review_document'
                                        )
                                            ->label('Dokumen')
                                            ->content(
                                                fn (Get $get): string => $this->documentLevel(
                                                    $get('mitra_id')
                                                )
                                            ),

                                        Forms\Components\Placeholder::make(
                                            'review_document_score'
                                        )
                                            ->label('Skor Dokumen')
                                            ->content(
                                                fn (Get $get): string => $this->documentScore(
                                                    $get('mitra_id')
                                                ) . ' / 4'
                                            ),
                                    ])
                                    ->columnSpanFull(),

                                Grid::make([
                                    'default' => 1,
                                    'md' => 2,
                                ])
                                    ->schema([
                                        Forms\Components\Placeholder::make(
                                            'total_score_preview'
                                        )
                                            ->label('FINAL SCORE')
                                            ->content(
                                                fn (Get $get): string => number_format(
                                                    $this->previewScore($get),
                                                    2,
                                                    ',',
                                                    '.'
                                                ) . ' / 100'
                                            ),

                                        Forms\Components\Placeholder::make(
                                            'score_status'
                                        )
                                            ->label('Status')
                                            ->content(
                                                fn (Get $get): string => $this->scoreStatus(
                                                    $this->previewScore($get)
                                                )
                                            ),
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\Placeholder::make(
                                    'review_information'
                                )
                                    ->label('Catatan')
                                    ->content(
                                        'Pastikan seluruh data yang dimasukkan merupakan data aktual. Setelah disimpan, ranking periode akan diperbarui.'
                                    )
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->compact()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ])
                ->persistStepInQueryString()
                ->skippable(false)
                ->columnSpanFull(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('mitra_id')

            ->modifyQueryUsing(
                fn ($query) => $query->with([
                    'mitra.kategori',
                    'mitra.negara',
                ])
            )

            ->striped()
->paginated([10, 25, 50])
->extraAttributes([
    'class' => 'text-sm [&_.fi-ta-row>td]:py-2',
])

            ->columns([
    Tables\Columns\TextColumn::make('ranking')
        ->label('#')
        ->badge()
        ->color(
            fn ($state): string => match ((int) $state) {
                1 => 'warning',
                2 => 'gray',
                3 => 'orange',
                default => 'primary',
            }
        )
        ->sortable()
        ->alignCenter()
        ->width('60px'),

    Tables\Columns\TextColumn::make('mitra.nama_mitra')
        ->label('Nama Mitra')
        ->searchable()
        ->sortable()
        ->limit(35)
        ->tooltip(
            fn ($record): ?string => $record->mitra?->nama_mitra
        )
        ->wrap(false),

    Tables\Columns\TextColumn::make('mitra.kategori.kategori')
        ->label('Kategori')
        ->badge()
        ->default('-')
        ->limit(20),

    Tables\Columns\TextColumn::make('mitra.negara.nama_negara')
        ->label('Negara')
        ->default('Indonesia')
        ->limit(18),

    Tables\Columns\TextColumn::make('dokumen_score')
        ->label('Dokumen')
        ->formatStateUsing(
            fn ($state): string => $this->documentLevelFromScore(
                (int) ($state ?? 0)
            )
        )
        ->badge()
        ->color(
            fn ($state): string => match ((int) $state) {
                4 => 'success',
                3 => 'info',
                2 => 'warning',
                1 => 'gray',
                default => 'danger',
            }
        )
        ->alignCenter(),

    Tables\Columns\TextColumn::make('total_score')
        ->label('Score')
        ->numeric(2)
        ->sortable()
        ->weight('bold')
        ->alignCenter(),
])

            ->filters([
                Tables\Filters\SelectFilter::make('participant_scope')
                    ->label('Tampilkan')
                    ->options([
                        'all' => 'Semua peserta',
                        'top3' => 'Top 3',
                        'top10' => 'Top 10',
                    ])
                    ->query(
                        function ($query, array $data) {
                            return match ($data['value'] ?? 'all') {
                                'top3' => $query->where('ranking', '<=', 3),
                                'top10' => $query->where('ranking', '<=', 10),
                                default => $query,
                            };
                        }
                    ),
            ])

            ->headerActions([
    Action::make('exportExcel')
        ->label('Export Excel')
        ->icon('heroicon-o-arrow-down-tray')
        ->color('success')
        ->action(function () {
            return Excel::download(
                new MitraAwardScoreExport(
                    $this->ownerRecord->getKey()
                ),
                'mitra-award-' . $this->ownerRecord->getKey() . '.xlsx'
            );
        }),

    CreateAction::make()
        ->label('Buat Mitra Award Score')
        ->icon('heroicon-o-plus')
        ->modalHeading('Buat Mitra Award Score')
        ->modalDescription(
            'Isi penilaian secara bertahap. Skor akhir akan dihitung otomatis.'
        )
        ->modalWidth('7xl')
        ->mutateFormDataUsing(
            fn (array $data): array => $this->prepareScoreData($data)
        ),
])

            ->recordActions([
                EditAction::make()
                    ->label('Edit Penilaian')
                    ->modalHeading('Edit Mitra Award Score')
                    ->modalWidth('7xl')
                    ->mutateFormDataUsing(
                        fn (array $data): array => $this->prepareScoreData($data)
                    ),

                DeleteAction::make()
                    ->label('Hapus'),
            ])

            ->defaultSort('ranking', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | FORM FIELD BUILDERS
    |--------------------------------------------------------------------------
    */

    private function countField(
        string $name,
        string $label,
        ?string $helper = null
    ): Forms\Components\TextInput {
        return Forms\Components\TextInput::make($name)
            ->label($label)
            ->numeric()
            ->minValue(0)
            ->default(0)
            ->required()
            ->live()
            ->step(1)
            ->suffix($this->unit($name))
            ->helperText($helper)
            ->columnSpan(1);
    }

    private function moneyField(
        string $name,
        string $label,
        ?string $helper = null
    ): Forms\Components\TextInput {
        return Forms\Components\TextInput::make($name)
            ->label($label)
            ->numeric()
            ->minValue(0)
            ->default(0)
            ->required()
            ->live()
            ->step(1)
            ->prefix('Rp')
            ->helperText($helper)
            ->columnSpan(1);
    }

    private function likertField(
        string $name,
        string $label,
        ?string $helper = null
    ): Forms\Components\Select {
        return Forms\Components\Select::make($name)
            ->label($label)
            ->options($this->likertOptions($name))
            ->default(0)
            ->required()
            ->live()
            ->native(false)
            ->helperText($helper)
            ->columnSpan(1);
    }

    /*
    |--------------------------------------------------------------------------
    | SCALE GUIDE
    |--------------------------------------------------------------------------
    */

    private function scaleGuideSection(array $criteria): Section
    {
        $schema = [];

        foreach ($criteria as $criterion) {
            $schema[] = Forms\Components\Placeholder::make(
                'scale_' . $criterion
            )
                ->label(
                    MitraAwardCalculator::CRITERIA_LABELS[$criterion]
                    ?? $criterion
                )
                ->content(
                    fn (): HtmlString => new HtmlString(
                        $this->scaleGuideHtml($criterion)
                    )
                )
                ->columnSpanFull();
        }

        return Section::make('Panduan Skala & Bobot')
            ->description(
                'Panduan pengisian indikator.'
            )
            ->schema($schema)
            ->collapsed()
            ->columns(1)
            ->compact()
            ->columnSpanFull();
    }

    private function scaleGuideHtml(string $name): string
    {
        $scale = $this->getScale($name);
        $weight = $this->getWeightPercentage($name);
        $unit = $this->unit($name);

        $label = e(
            MitraAwardCalculator::CRITERIA_LABELS[$name] ?? $name
        );

        $html = '<div class="space-y-1 text-sm">';

        $html .= '<div>'
            . '<strong>Bobot:</strong> '
            . number_format($weight, 2, ',', '.')
            . '%';

        if ($unit !== '') {
            $html .= ' &nbsp;•&nbsp; '
                . '<strong>Satuan:</strong> '
                . e($unit);
        }

        $html .= '</div>';

        if (! $scale) {
            $html .= '<div class="text-gray-500">'
                . $label
                . ': skala default digunakan.</div>';

            $html .= '</div>';

            return $html;
        }

        $type = $scale['type'] ?? 'count';

        if ($type === 'likert' || $name === 'dokumen_score') {
            $labels = $scale['labels'] ?? [];

            $html .= '<div class="mt-2 grid grid-cols-1 gap-1 sm:grid-cols-2 lg:grid-cols-5">';

            for ($score = 0; $score <= 4; $score++) {
                $scoreLabel = e(
                    $labels[$score]
                    ?? $this->defaultScoreLabel($score)
                );

                $html .= '<div class="rounded-md border border-gray-200 px-2 py-1 dark:border-gray-700">'
                    . '<strong>Skor ' . $score . ':</strong> '
                    . $scoreLabel
                    . '</div>';
            }

            $html .= '</div>';
        } else {
            $thresholds = $scale['thresholds'] ?? [];

            $html .= '<div class="mt-2 grid grid-cols-1 gap-1 sm:grid-cols-2 lg:grid-cols-5">';

            for ($score = 0; $score <= 4; $score++) {
                $html .= '<div class="rounded-md border border-gray-200 px-2 py-1 dark:border-gray-700">'
                    . '<strong>Skor ' . $score . ':</strong> '
                    . e(
                        $this->thresholdDescription(
                            $thresholds,
                            $score,
                            $type
                        )
                    )
                    . '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /*
    |--------------------------------------------------------------------------
    | OPTIONS
    |--------------------------------------------------------------------------
    */

    private function likertOptions(string $name): array
    {
        $scale = $this->getScale($name);

        $labels = $scale['labels'] ?? [];

        return [
            0 => $labels[0] ?? 'Tidak ada / Sangat rendah',
            1 => $labels[1] ?? 'Rendah',
            2 => $labels[2] ?? 'Sedang',
            3 => $labels[3] ?? 'Tinggi',
            4 => $labels[4] ?? 'Sangat tinggi',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIGURATION
    |--------------------------------------------------------------------------
    */

    private function getConfig(): array
    {
        $calculator = app(MitraAwardCalculator::class);

        $default = $calculator->defaultConfig();

        $periodConfig = $this->ownerRecord->konfigurasi_penilaian;

        if (! is_array($periodConfig)) {
            $periodConfig = [];
        }

        return array_replace_recursive(
            $default,
            $periodConfig
        );
    }

    private function getScale(string $name): ?array
    {
        $config = $this->getConfig();

        $scale = $config['skala'][$name]
            ?? $config['scales'][$name]
            ?? null;

        return is_array($scale) ? $scale : null;
    }

    private function getWeightPercentage(string $name): float
    {
        $config = $this->getConfig();

        return (float) (
            ($config['bobot'][$name] ?? 0) * 100
        );
    }

    private function unit(string $name): string
    {
        return MitraAwardCalculator::CRITERIA_UNITS[$name]
            ?? '';
    }

    /*
    |--------------------------------------------------------------------------
    | SCALE HELPERS
    |--------------------------------------------------------------------------
    */

    private function thresholdDescription(
        array $thresholds,
        int $score,
        string $type
    ): string {
        if ($score === 4) {
            $last = $thresholds[3] ?? 0;

            return '> ' . $this->formatThreshold(
                $last,
                $type
            );
        }

        $value = $thresholds[$score] ?? 0;

        return '≤ ' . $this->formatThreshold(
            $value,
            $type
        );
    }

    private function formatThreshold(
        mixed $value,
        string $type
    ): string {
        $value = (float) $value;

        if ($type === 'money') {
            return 'Rp ' . $this->formatMoney($value);
        }

        return number_format(
            $value,
            0,
            ',',
            '.'
        );
    }

    private function formatMoney(float|int $value): string
    {
        $value = (float) $value;

        if ($value >= 1_000_000_000) {
            return number_format(
                $value / 1_000_000_000,
                2,
                ',',
                '.'
            ) . ' M';
        }

        if ($value >= 1_000_000) {
            return number_format(
                $value / 1_000_000,
                2,
                ',',
                '.'
            ) . ' juta';
        }

        if ($value >= 1_000) {
            return number_format(
                $value / 1_000,
                2,
                ',',
                '.'
            ) . ' ribu';
        }

        return number_format(
            $value,
            0,
            ',',
            '.'
        );
    }

    private function defaultScoreLabel(int $score): string
    {
        return match ($score) {
            0 => 'Tidak ada',
            1 => 'Rendah',
            2 => 'Sedang',
            3 => 'Tinggi',
            4 => 'Sangat tinggi',
            default => '-',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | MITRA INFORMATION
    |--------------------------------------------------------------------------
    */

    private function findMitra(mixed $mitraId): ?Mitra
    {
        if (! $mitraId) {
            return null;
        }

        return Mitra::with([
            'kategori',
            'negara',
        ])->find((int) $mitraId);
    }

    private function mitraName(mixed $mitraId): string
    {
        return $this->findMitra($mitraId)?->nama_mitra
            ?? '-';
    }

    private function mitraCategory(mixed $mitraId): string
    {
        $mitra = $this->findMitra($mitraId);

        return $mitra?->kategori?->kategori
            ?? '-';
    }

    private function mitraCountry(mixed $mitraId): string
    {
        $mitra = $this->findMitra($mitraId);

        return $mitra?->negara?->nama_negara
            ?? 'Indonesia';
    }

    /*
    |--------------------------------------------------------------------------
    | DOCUMENT SCORE
    |--------------------------------------------------------------------------
    */

    private function documentScore(mixed $mitraId): int
    {
        if (! $mitraId) {
            return 0;
        }

        $mitra = $this->findMitra($mitraId);

        if (! $mitra) {
            return 0;
        }

        return (int) app(
            MitraAwardCalculator::class
        )->getDocumentScore($mitra);
    }

    private function documentLevel(mixed $mitraId): string
    {
        if (! $mitraId) {
            return 'Pilih mitra terlebih dahulu';
        }

        return $this->documentLevelFromScore(
            $this->documentScore($mitraId)
        );
    }

    private function documentLevelFromScore(int $score): string
    {
        return match ($score) {
            0 => 'Tidak ada',
            1 => 'Inisiasi / tracking',
            2 => 'IA',
            3 => 'PKS / SPK',
            4 => 'MoU',
            default => '-',
        };
    }

    private function documentExplanation(mixed $mitraId): string
    {
        if (! $mitraId) {
            return 'Pilih mitra terlebih dahulu untuk mendeteksi dokumen kerja sama.';
        }

        $score = $this->documentScore($mitraId);

        return match ($score) {
            4 => 'Mitra memiliki MoU. Skor dokumen otomatis = 4.',
            3 => 'Mitra memiliki PKS atau SPK dan belum ditemukan MoU. Skor otomatis = 3.',
            2 => 'Mitra memiliki IA dan belum ditemukan PKS/SPK/MoU. Skor otomatis = 2.',
            1 => 'Mitra memiliki dokumen/inisiasi kerja sama atau usulan kerja sama. Skor otomatis = 1.',
            default => 'Belum ditemukan dokumen atau usulan kerja sama. Skor otomatis = 0.',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | SCORE CALCULATION
    |--------------------------------------------------------------------------
    */

    private function previewScore(Get $get): float
    {
        $mitraId = $get('mitra_id');

        $score = new MitraAwardScore([
            'mitra_award_period_id' => $this->ownerRecord->getKey(),

            'kurikulum' => (int) ($get('kurikulum') ?? 0),
            'magang' => (int) ($get('magang') ?? 0),
            'dosen_industri' => (int) ($get('dosen_industri') ?? 0),
            'rekrutmen' => (int) ($get('rekrutmen') ?? 0),

            'penelitian_cash' => (float) ($get('penelitian_cash') ?? 0),
            'penelitian_kind' => (float) ($get('penelitian_kind') ?? 0),

            'hilirisasi' => (int) ($get('hilirisasi') ?? 0),
            'khalayak_pkm' => (int) ($get('khalayak_pkm') ?? 0),
            'publikasi_bersama' => (int) ($get('publikasi_bersama') ?? 0),
            'co_hosting' => (int) ($get('co_hosting') ?? 0),

            'pelatihan_sertifikasi' => (int) (
                $get('pelatihan_sertifikasi') ?? 0
            ),

            'kajian_tenaga_ahli' => (float) (
                $get('kajian_tenaga_ahli') ?? 0
            ),

            'hibah_alat' => (float) (
                $get('hibah_alat') ?? 0
            ),

            'reputasi' => (int) ($get('reputasi') ?? 0),

            'perluasan_jejaring' => (int) (
                $get('perluasan_jejaring') ?? 0
            ),
        ]);

        $score->setRelation(
            'period',
            $this->ownerRecord
        );

        $mitra = $mitraId
            ? $this->findMitra($mitraId)
            : null;

        $calculator = app(
            MitraAwardCalculator::class
        );

        $score->dokumen_score = $mitra
            ? (int) $calculator->getDocumentScore($mitra)
            : 0;

        return (float) $calculator->calculate($score);
    }

    private function scoreStatus(float $score): string
    {
        if ($score >= 80) {
            return 'Sangat tinggi';
        }

        if ($score >= 60) {
            return 'Tinggi';
        }

        if ($score >= 40) {
            return 'Sedang';
        }

        if ($score > 0) {
            return 'Rendah';
        }

        return 'Belum dinilai';
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE DATA
    |--------------------------------------------------------------------------
    */

    private function prepareScoreData(array $data): array
    {
        $mitraId = $data['mitra_id'] ?? null;

        $mitra = $mitraId
            ? $this->findMitra($mitraId)
            : null;

        $calculator = app(
            MitraAwardCalculator::class
        );

        $data['dokumen_score'] = $mitra
            ? (int) $calculator->getDocumentScore($mitra)
            : 0;

        $score = new MitraAwardScore([
            'mitra_award_period_id' => $this->ownerRecord->getKey(),

            'kurikulum' => (int) ($data['kurikulum'] ?? 0),
            'magang' => (int) ($data['magang'] ?? 0),
            'dosen_industri' => (int) ($data['dosen_industri'] ?? 0),
            'rekrutmen' => (int) ($data['rekrutmen'] ?? 0),

            'penelitian_cash' => (float) ($data['penelitian_cash'] ?? 0),
            'penelitian_kind' => (float) ($data['penelitian_kind'] ?? 0),

            'hilirisasi' => (int) ($data['hilirisasi'] ?? 0),
            'khalayak_pkm' => (int) ($data['khalayak_pkm'] ?? 0),
            'publikasi_bersama' => (int) ($data['publikasi_bersama'] ?? 0),
            'co_hosting' => (int) ($data['co_hosting'] ?? 0),

            'pelatihan_sertifikasi' => (int) (
                $data['pelatihan_sertifikasi'] ?? 0
            ),

            'kajian_tenaga_ahli' => (float) (
                $data['kajian_tenaga_ahli'] ?? 0
            ),

            'hibah_alat' => (float) (
                $data['hibah_alat'] ?? 0
            ),

            'reputasi' => (int) ($data['reputasi'] ?? 0),

            'perluasan_jejaring' => (int) (
                $data['perluasan_jejaring'] ?? 0
            ),
        ]);

        $score->setRelation(
            'period',
            $this->ownerRecord
        );

        $score->dokumen_score = $data['dokumen_score'];

        $data['total_score'] = $calculator->calculate(
            $score
        );

        return $data;
    }
}