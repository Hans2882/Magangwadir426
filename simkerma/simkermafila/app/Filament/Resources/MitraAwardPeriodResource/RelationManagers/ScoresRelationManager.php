<?php

namespace App\Filament\Resources\MitraAwardPeriodResource\RelationManagers;

use App\Models\Mitra;
use App\Models\MitraAwardScore;
use App\Services\MitraAwardCalculator;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class ScoresRelationManager extends RelationManager
{
    protected static string $relationship = 'scores';

    protected static ?string $title = 'Peserta dan Penilaian';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            /*
             * ============================================================
             * MITRA + DOKUMEN
             * ============================================================
             */
            Section::make('Ketersediaan Dokumen')
                ->description('Level dokumen dihitung otomatis berdasarkan dokumen kerja sama mitra.')
                ->schema([
                    Forms\Components\Select::make('mitra_id')
                        ->label('Mitra')
                        ->relationship('mitra', 'nama_mitra')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->rule(function (?MitraAwardScore $record) {
                            return Rule::unique('mitra_award_scores', 'mitra_id')
                                ->where(
                                    fn ($query) => $query->where(
                                        'mitra_award_period_id',
                                        $this->ownerRecord->getKey()
                                    )
                                )
                                ->ignore($record?->getKey());
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            $mitra = $state ? Mitra::find($state) : null;

                            if (! $mitra) {
                                return;
                            }

                            $calculator = app(MitraAwardCalculator::class);
                            $documentScore = $calculator->getDocumentScore($mitra);

                            $set('document_score_preview', $documentScore);
                        }),

                    Forms\Components\Placeholder::make('document_level')
                        ->label('Jenis dokumen terdeteksi')
                        ->content(
                            fn (Get $get): string => $this->documentLevel(
                                $get('mitra_id')
                            )
                        ),

                    Forms\Components\Placeholder::make('document_score_preview')
                        ->label('Skor dokumen')
                        ->content(
                            fn (Get $get): string => (string) app(
                                MitraAwardCalculator::class
                            )->getDocumentScore(
                                Mitra::find($get('mitra_id'))
                            )
                        ),
                ])
                ->columns(3),

            /*
             * ============================================================
             * KEMITRAAN AKADEMIK
             * ============================================================
             */
            Section::make('Kemitraan Akademik')
                ->schema([
                    $this->countField('kurikulum', 'Kurikulum'),
                    $this->countField('magang', 'Magang'),
                    $this->countField('dosen_industri', 'Dosen Industri'),
                    $this->countField('rekrutmen', 'Rekrutmen'),
                ])
                ->columns(2),

            /*
             * ============================================================
             * PENELITIAN DAN PKM
             * ============================================================
             */
            Section::make('Penelitian dan Pengabdian kepada Masyarakat')
                ->schema([
                    $this->moneyField('penelitian_cash', 'Penelitian Cash'),
                    $this->moneyField('penelitian_kind', 'Penelitian In-kind'),
                    $this->countField('hilirisasi', 'Hilirisasi'),
                    $this->countField('khalayak_pkm', 'Khalayak PKM'),
                    $this->countField('publikasi_bersama', 'Publikasi Bersama'),
                    $this->countField('co_hosting', 'Co-hosting'),
                ])
                ->columns(2),

            /*
             * ============================================================
             * INCOME GENERATION
             * ============================================================
             */
            Section::make('Income Generation')
                ->schema([
                    $this->countField(
                        'pelatihan_sertifikasi',
                        'Pelatihan / Sertifikasi'
                    ),

                    $this->moneyField(
                        'kajian_tenaga_ahli',
                        'Kajian Tenaga Ahli'
                    ),

                    $this->moneyField(
                        'hibah_alat',
                        'Hibah Alat'
                    ),
                ])
                ->columns(2),

            /*
             * ============================================================
             * NILAI TAMBAH
             * ============================================================
             */
            Section::make('Nilai Tambah')
                ->schema([
                    $this->likertField(
                        'reputasi',
                        'Reputasi'
                    ),

                    $this->likertField(
                        'perluasan_jejaring',
                        'Perluasan Jejaring'
                    ),
                ])
                ->columns(2),

            /*
             * ============================================================
             * PREVIEW NILAI
             * ============================================================
             */
            Section::make('Preview Nilai')
                ->schema([
                    Forms\Components\Placeholder::make('total_score_preview')
                        ->label('Final Score')
                        ->content(
                            fn (Get $get): string => number_format(
                                $this->previewScore($get),
                                2,
                                ',',
                                '.'
                            ) . ' / 100'
                        ),
                ]),
        ]);
    }

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

            ->columns([
                Tables\Columns\TextColumn::make('ranking')
                    ->label('Ranking')
                    ->badge()
                    ->color(
                        fn (?int $state): string => match ($state) {
                            1 => 'warning',
                            2 => 'gray',
                            3 => 'orange',
                            default => 'primary',
                        }
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('mitra.nama_mitra')
                    ->label('Nama Mitra')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('mitra.kategori.kategori')
                    ->label('Kategori IKU')
                    ->badge()
                    ->default('-'),

                Tables\Columns\TextColumn::make('mitra.negara.nama_negara')
                    ->label('Negara')
                    ->default('Indonesia'),

                Tables\Columns\TextColumn::make('dokumen_score')
                    ->label('Level Dokumen')
                    ->formatStateUsing(
                        fn (int $state): string => [
                            0 => 'Tidak ada',
                            1 => 'Inisiasi / tracking',
                            2 => 'IA',
                            3 => 'PKS / SPK',
                            4 => 'MoU',
                        ][$state] ?? '-'
                    ),

                Tables\Columns\TextColumn::make('total_score')
                    ->label('Final Score')
                    ->numeric(2)
                    ->sortable(),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('participant_scope')
                    ->label('Tampilkan')
                    ->options([
                        'all' => 'Semua peserta',
                        'top3' => 'Top 3',
                        'top10' => 'Top 10',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? 'all') {
                            'top3' => $query->where('ranking', '<=', 3),
                            'top10' => $query->where('ranking', '<=', 10),
                            default => $query,
                        };
                    }),
            ])

            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Peserta'),
            ])

            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])

            ->defaultSort('ranking');
    }

    /*
     * ================================================================
     * COUNT FIELD
     * ================================================================
     *
     * Contoh:
     *
     * 0      -> Tidak ada
     * 1      -> 1
     * 2      -> 2
     * 3      -> 3
     * 4      -> > 3
     *
     * Nilai yang disimpan tetap 0,1,2,3,4.
     * Calculator kemudian menerjemahkan nilai tersebut menjadi skor.
     */
    private function countField(
        string $name,
        string $label
    ): Forms\Components\Select {
        return Forms\Components\Select::make($name)
            ->label($label)
            ->options($this->countOptions($name))
            ->default(0)
            ->required()
            ->live()
            ->native(false);
    }

    /*
     * ================================================================
     * MONEY FIELD
     * ================================================================
     *
     * Pilihan mengikuti threshold dari konfigurasi periode.
     */
    private function moneyField(
        string $name,
        string $label
    ): Forms\Components\Select {
        return Forms\Components\Select::make($name)
            ->label($label)
            ->options($this->moneyOptions($name))
            ->default(0)
            ->required()
            ->live()
            ->native(false);
    }

    /*
     * ================================================================
     * LIKERT FIELD
     * ================================================================
     */
    private function likertField(
        string $name,
        string $label
    ): Forms\Components\Select {
        return Forms\Components\Select::make($name)
            ->label($label)
            ->options($this->likertOptions($name))
            ->default(0)
            ->required()
            ->live()
            ->native(false);
    }

    /*
     * ================================================================
     * COUNT OPTIONS
     * ================================================================
     */
    private function countOptions(string $name): array
    {
        $scale = $this->getScale($name);

        if (! $scale) {
            return [
                0 => 'Tidak ada (0)',
                1 => '1',
                2 => '2',
                3 => '3',
                4 => 'Lebih dari 3',
            ];
        }

        $thresholds = $scale['thresholds'] ?? [0, 1, 2, 3];

        return [
            0 => $this->countOptionLabel(
                $thresholds,
                0
            ),

            1 => $this->countOptionLabel(
                $thresholds,
                1
            ),

            2 => $this->countOptionLabel(
                $thresholds,
                2
            ),

            3 => $this->countOptionLabel(
                $thresholds,
                3
            ),

            4 => $this->countOptionLabel(
                $thresholds,
                4
            ),
        ];
    }

    private function countOptionLabel(
        array $thresholds,
        int $score
    ): string {
        if ($score === 0) {
            return '0 / Tidak ada';
        }

        if ($score === 4) {
            $last = $thresholds[3] ?? 3;

            return '> ' . number_format(
                (float) $last,
                0,
                ',',
                '.'
            );
        }

        $value = $thresholds[$score] ?? $score;

        return number_format(
            (float) $value,
            0,
            ',',
            '.'
        );
    }

    /*
     * ================================================================
     * MONEY OPTIONS
     * ================================================================
     */
    private function moneyOptions(string $name): array
    {
        $scale = $this->getScale($name);

        if (! $scale) {
            return [
                0 => 'Rp 0',
                1 => 'Sesuai batas skor 1',
                2 => 'Sesuai batas skor 2',
                3 => 'Sesuai batas skor 3',
                4 => 'Di atas batas skor 3',
            ];
        }

        $thresholds = $scale['thresholds'] ?? [
            0,
            2_000_000,
            5_000_000,
            10_000_000,
        ];

        return [
            0 => 'Rp 0',

            1 => 'Rp ' . $this->formatMoney(
                $thresholds[1] ?? 0
            ),

            2 => 'Rp ' . $this->formatMoney(
                $thresholds[2] ?? 0
            ),

            3 => 'Rp ' . $this->formatMoney(
                $thresholds[3] ?? 0
            ),

            4 => '> Rp ' . $this->formatMoney(
                $thresholds[3] ?? 0
            ),
        ];
    }

    /*
     * ================================================================
     * LIKERT OPTIONS
     * ================================================================
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
     * ================================================================
     * GET SCALE FROM CURRENT PERIOD
     * ================================================================
     *
     * Prioritas:
     *
     * 1. konfigurasi_penilaian milik periode
     * 2. defaultConfig() dari Calculator
     */
    private function getScale(string $name): ?array
    {
        $calculator = app(MitraAwardCalculator::class);

        $config = $this->ownerRecord->konfigurasi_penilaian;

        if (! is_array($config) || empty($config)) {
            $config = $calculator->defaultConfig();
        }

        return $config['skala'][$name]
            ?? $config['scales'][$name]
            ?? null;
    }

    /*
     * ================================================================
     * FORMAT MONEY
     * ================================================================
     */
    private function formatMoney(float|int $value): string
    {
        if ($value >= 1_000_000_000) {
            return number_format(
                $value / 1_000_000_000,
                0,
                ',',
                '.'
            ) . ' M';
        }

        if ($value >= 1_000_000) {
            return number_format(
                $value / 1_000_000,
                0,
                ',',
                '.'
            ) . ' juta';
        }

        if ($value >= 1_000) {
            return number_format(
                $value / 1_000,
                0,
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

    /*
     * ================================================================
     * DOCUMENT LEVEL
     * ================================================================
     */
    private function documentLevel(?int $mitraId): string
    {
        if (! $mitraId) {
            return '-';
        }

        $mitra = Mitra::find($mitraId);

        if (! $mitra) {
            return '-';
        }

        $score = app(MitraAwardCalculator::class)
            ->getDocumentScore($mitra);

        return [
            0 => 'Tidak ada',
            1 => 'Inisiasi / tracking',
            2 => 'IA',
            3 => 'PKS / SPK',
            4 => 'MoU',
        ][$score] ?? '-';
    }

    /*
     * ================================================================
     * PREVIEW SCORE
     * ================================================================
     */
    private function previewScore(Get $get): float
    {
        $mitraId = $get('mitra_id');

        $score = new MitraAwardScore([
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

        $mitra = $mitraId
            ? Mitra::find($mitraId)
            : null;

        $calculator = app(MitraAwardCalculator::class);

        $score->dokumen_score = $mitra
            ? $calculator->getDocumentScore($mitra)
            : 0;

        return $calculator->calculate($score);
    }
}