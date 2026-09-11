<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MitraAwardPeriodResource\Pages;
use App\Filament\Resources\MitraAwardPeriodResource\RelationManagers\ScoresRelationManager;
use App\Models\MitraAwardPeriod;
use App\Services\MitraAwardCalculator;
use App\Services\MitraAwardRanking;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use UnitEnum;

class MitraAwardPeriodResource extends Resource
{
    protected static ?string $model = MitraAwardPeriod::class;

    /*
    |--------------------------------------------------------------------------
    | NAVIGATION
    |--------------------------------------------------------------------------
    */

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-calendar-days';

    protected static ?string $navigationLabel =
        'Periode Award Mitra';

    protected static ?string $modelLabel =
        'Periode Award Mitra';

    protected static ?string $pluralModelLabel =
        'Periode Award Mitra';

    protected static UnitEnum|string|null $navigationGroup =
        'Mitra Award';

    protected static ?int $navigationSort = 1;

    /*
    |--------------------------------------------------------------------------
    | FORM
    |--------------------------------------------------------------------------
    */

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Periode')
                ->schema([
                    TextInput::make('nama')
                        ->label('Nama Periode')
                        ->required()
                        ->maxLength(255)
                        ->placeholder(
                            'Contoh: Mitra Award 2026'
                        ),

                    TextInput::make('tahun')
                        ->label('Tahun')
                        ->numeric()
                        ->required()
                        ->minValue(2000)
                        ->maxValue(2100),

                    DatePicker::make('tanggal_mulai')
                        ->label('Tanggal Mulai')
                        ->required()
                        ->native(false),

                    DatePicker::make('tanggal_selesai')
                        ->label('Tanggal Selesai')
                        ->required()
                        ->native(false)
                        ->afterOrEqual(
                            'tanggal_mulai'
                        ),

                    Forms\Components\Toggle::make(
                        'is_active'
                    )
                        ->label('Periode Aktif')
                        ->helperText(
                            'Jika diaktifkan, periode aktif sebelumnya akan otomatis dinonaktifkan.'
                        )
                        ->default(false),
                ])
                ->columns(2),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Periode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make(
                    'scores_count'
                )
                    ->label('Jumlah Mitra')
                    ->counts('scores')
                    ->badge()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make(
                    'is_active'
                )
                    ->label('Aktif')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make(
                    'tanggal_mulai'
                )
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'tanggal_selesai'
                )
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),
            ])

            ->defaultSort(
                'tahun',
                'desc'
            )

            ->recordUrl(
                fn (
                    MitraAwardPeriod $record
                ): string => static::getUrl(
                    'view',
                    [
                        'record' => $record,
                    ]
                )
            )

            ->actions([
                ViewAction::make(),

                /*
                |--------------------------------------------------------------------------
                | KONFIGURASI PENILAIAN
                |--------------------------------------------------------------------------
                */

                Action::make(
                    'konfigurasi_penilaian'
                )
                    ->label('Skala & Bobot')
                    ->icon(
                        'heroicon-o-adjustments-horizontal'
                    )
                    ->color('warning')

                    ->modalHeading(
                        fn (
                            MitraAwardPeriod $record
                        ): string =>
                            'Skala & Bobot — ' .
                            $record->nama
                    )

                    ->modalDescription(
                        'Atur bobot dan skala penilaian khusus untuk periode ini.'
                    )

                    ->modalWidth('3xl')

                    ->fillForm(
                        fn (
                            MitraAwardPeriod $record
                        ): array =>
                            static::configurationFormData(
                                $record
                            )
                    )

                    ->form([
                        /*
                        |--------------------------------------------------------------------------
                        | BOBOT
                        |--------------------------------------------------------------------------
                        */

                        Section::make(
                            'Bobot Penilaian'
                        )
                            ->description(
                                'Total seluruh bobot harus tepat 100%.'
                            )
                            ->schema([
                                Repeater::make(
                                    'bobot'
                                )
                                    ->label('')
                                    ->schema([
                                        TextInput::make(
                                            'kriteria'
                                        )
                                            ->label('Kriteria')
                                            ->disabled()
                                            ->dehydrated(true)
                                            ->formatStateUsing(
                                                fn (
                                                    ?string $state
                                                ): string =>
                                                    MitraAwardCalculator::CRITERIA_LABELS[
                                                        $state
                                                    ]
                                                    ?? $state
                                                    ?? '-'
                                            ),

                                        TextInput::make(
                                            'nilai'
                                        )
                                            ->label('Bobot (%)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->step(0.1)
                                            ->suffix('%')
                                            ->required()
                                            ->live(),
                                    ])
                                    ->columns(2)
                                    ->disableItemCreation()
                                    ->disableItemDeletion()
                                    ->disableItemMovement()
                                    ->reorderable(false),
                            ]),

                        Placeholder::make('total_bobot_summary')
                            ->label('Total Bobot')
                            ->content(
                                fn (
                                    Get $get
                                ): HtmlString =>
                                    static::totalBobotHtml(
                                        $get('bobot')
                                    )
                            ),

                        /*
                        |--------------------------------------------------------------------------
                        | SKALA
                        |--------------------------------------------------------------------------
                        */

                        Section::make(
                            'Skala Penilaian'
                        )
                            ->description(
                                'Atur tipe skala dan nilai batas masing-masing kriteria.'
                            )
                            ->schema([
                                Repeater::make(
                                    'skala'
                                )
                                    ->label('')
                                    ->schema([
                                        TextInput::make(
                                            'kriteria'
                                        )
                                            ->label('Kriteria')
                                            ->disabled()
                                            ->dehydrated(true)
                                            ->formatStateUsing(
                                                fn (
                                                    ?string $state
                                                ): string =>
                                                    MitraAwardCalculator::CRITERIA_LABELS[
                                                        $state
                                                    ]
                                                    ?? $state
                                                    ?? '-'
                                            ),

                                        Select::make(
                                            'type'
                                        )
                                            ->label('Tipe Skala')
                                            ->options([
                                                'count' => 'Jumlah',
                                                'money' => 'Nominal Uang',
                                                'likert' => 'Likert',
                                                'document' => 'Dokumen',
                                            ])
                                            ->required()
                                            ->live(),

                                        TextInput::make(
                                            'thresholds.0'
                                        )
                                            ->label('Batas 1')
                                            ->numeric()
                                            ->required()
                                            ->visible(
                                                fn (
                                                    Get $get
                                                ): bool =>
                                                    ! in_array(
                                                        $get('type'),
                                                        ['likert', 'document'],
                                                        true
                                                    )
                                            ),

                                        TextInput::make(
                                            'thresholds.1'
                                        )
                                            ->label('Batas 2')
                                            ->numeric()
                                            ->required()
                                            ->visible(
                                                fn (
                                                    Get $get
                                                ): bool =>
                                                    ! in_array(
                                                        $get('type'),
                                                        ['likert', 'document'],
                                                        true
                                                    )
                                            ),

                                        TextInput::make(
                                            'thresholds.2'
                                        )
                                            ->label('Batas 3')
                                            ->numeric()
                                            ->required()
                                            ->visible(
                                                fn (
                                                    Get $get
                                                ): bool =>
                                                    ! in_array(
                                                        $get('type'),
                                                        ['likert', 'document'],
                                                        true
                                                    )
                                            ),

                                        TextInput::make(
                                            'thresholds.3'
                                        )
                                            ->label('Batas 4')
                                            ->numeric()
                                            ->required()
                                            ->visible(
                                                fn (
                                                    Get $get
                                                ): bool =>
                                                    ! in_array(
                                                        $get('type'),
                                                        ['likert', 'document'],
                                                        true
                                                    )
                                            ),

                                        TextInput::make(
                                            'labels.0'
                                        )
                                            ->label('Skor 0')
                                            ->visible(
                                                fn (
                                                    Get $get
                                                ): bool =>
                                                    in_array(
                                                        $get('type'),
                                                        ['likert', 'document'],
                                                        true
                                                    )
                                            ),

                                        TextInput::make(
                                            'labels.1'
                                        )
                                            ->label('Skor 1')
                                            ->visible(
                                                fn (
                                                    Get $get
                                                ): bool =>
                                                    in_array(
                                                        $get('type'),
                                                        ['likert', 'document'],
                                                        true
                                                    )
                                            ),

                                        TextInput::make(
                                            'labels.2'
                                        )
                                            ->label('Skor 2')
                                            ->visible(
                                                fn (
                                                    Get $get
                                                ): bool =>
                                                    in_array(
                                                        $get('type'),
                                                        ['likert', 'document'],
                                                        true
                                                    )
                                            ),

                                        TextInput::make(
                                            'labels.3'
                                        )
                                            ->label('Skor 3')
                                            ->visible(
                                                fn (
                                                    Get $get
                                                ): bool =>
                                                    in_array(
                                                        $get('type'),
                                                        ['likert', 'document'],
                                                        true
                                                    )
                                            ),

                                        TextInput::make(
                                            'labels.4'
                                        )
                                            ->label('Skor 4')
                                            ->visible(
                                                fn (
                                                    Get $get
                                                ): bool =>
                                                    in_array(
                                                        $get('type'),
                                                        ['likert', 'document'],
                                                        true
                                                    )
                                            ),

                                        Placeholder::make('preview')
                                            ->label('Ringkasan Skala')
                                            ->content(
                                                fn (
                                                    Get $get
                                                ): HtmlString =>
                                                    static::scaleDescription(
                                                        $get('../')
                                                    )
                                            )
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->disableItemCreation()
                                    ->disableItemDeletion()
                                    ->disableItemMovement()
                                    ->reorderable(false),
                            ]),
                    ])

                    /*
                    |--------------------------------------------------------------------------
                    | SAVE CONFIGURATION
                    |--------------------------------------------------------------------------
                    */

                    ->action(
                        function (
                            MitraAwardPeriod $record,
                            array $data
                        ): void {
                            /*
                            |--------------------------------------------------------------------------
                            | NORMALIZE
                            |--------------------------------------------------------------------------
                            */

                            $config =
                                static::normalizeConfiguration(
                                    $data
                                );

                            /*
                            |--------------------------------------------------------------------------
                            | VALIDASI BOBOT
                            |--------------------------------------------------------------------------
                            */

                            $weights = [];

                            foreach (
                                ($config['bobot'] ?? [])
                                as $key => $value
                            ) {
                                $weights[$key] =
                                    (float) $value;
                            }

                            $calculator =
                                app(
                                    MitraAwardCalculator::class
                                );

                            if (
                                ! $calculator->validateWeights(
                                    $weights
                                )
                            ) {
                                $total =
                                    $calculator->getWeightPercentage(
                                        $weights
                                    );

                                Notification::make()
                                    ->title(
                                        'Bobot tidak valid'
                                    )
                                    ->body(
                                        'Total bobot harus tepat 100%. Saat ini totalnya ' .
                                        number_format(
                                            $total,
                                            2,
                                            ',',
                                            '.'
                                        ) .
                                        '%.'
                                    )
                                    ->danger()
                                    ->send();

                                return;
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | SIMPAN KONFIGURASI
                            |--------------------------------------------------------------------------
                            */

                            $record->update([
                                'konfigurasi_penilaian' =>
                                    $config,
                            ]);

                            /*
                            |--------------------------------------------------------------------------
                            | RECALCULATE SCORE
                            |--------------------------------------------------------------------------
                            */

                            $scores = $record
                                ->scores()
                                ->with('mitra')
                                ->get();

                            foreach (
                                $scores as $score
                            ) {
                                $documentScore =
                                    $calculator
                                        ->getDocumentScore(
                                            $score->mitra
                                        );

                                /*
                                | Pastikan calculator menggunakan
                                | periode yang sedang dikonfigurasi.
                                */

                                $score->setRelation(
                                    'period',
                                    $record
                                );

                                $score->dokumen_score =
                                    $documentScore;

                                $score->total_score =
                                    $calculator->calculate(
                                        $score
                                    );

                                $score->updateQuietly([
                                    'dokumen_score' =>
                                        $score->dokumen_score,

                                    'total_score' =>
                                        $score->total_score,
                                ]);
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | RECALCULATE RANKING
                            |--------------------------------------------------------------------------
                            */

                            app(
                                MitraAwardRanking::class
                            )->recalculate(
                                $record->getKey()
                            );

                            /*
                            |--------------------------------------------------------------------------
                            | NOTIFICATION
                            |--------------------------------------------------------------------------
                            */

                            Notification::make()
                                ->title(
                                    'Konfigurasi berhasil disimpan'
                                )
                                ->body(
                                    'Bobot, skala, nilai, dan ranking mitra telah diperbarui.'
                                )
                                ->success()
                                ->send();
                        }
                    ),

                EditAction::make(),

                DeleteAction::make(),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | INFOLIST
    |--------------------------------------------------------------------------
    */

    public static function infolist(
        Schema $schema
    ): Schema {
        return $schema->components([
            Section::make(
                'Informasi Periode'
            )
                ->schema([
                    TextEntry::make('nama')
                        ->label(
                            'Nama Periode'
                        ),

                    TextEntry::make('tahun')
                        ->label('Tahun'),

                    TextEntry::make(
                        'tanggal_mulai'
                    )
                        ->label(
                            'Tanggal Mulai'
                        )
                        ->date('d M Y'),

                    TextEntry::make(
                        'tanggal_selesai'
                    )
                        ->label(
                            'Tanggal Selesai'
                        )
                        ->date('d M Y'),

                    TextEntry::make(
                        'is_active'
                    )
                        ->label('Status')
                        ->formatStateUsing(
                            fn (
                                bool $state
                            ): string =>
                                $state
                                    ? 'Aktif'
                                    : 'Tidak Aktif'
                        )
                        ->badge()
                        ->color(
                            fn (
                                bool $state
                            ): string =>
                                $state
                                    ? 'success'
                                    : 'gray'
                        ),
                ])
                ->columns(2),

            Section::make(
                'Top 3 Mitra'
            )
                ->schema([
                    RepeatableEntry::make(
                        'scores'
                    )
                        ->label('')
                        ->state(
                            fn (
                                MitraAwardPeriod $record
                            ) =>
                                $record
                                    ->scores()
                                    ->with([
                                        'mitra.kategori',
                                        'mitra.negara',
                                    ])
                                    ->orderBy(
                                        'ranking'
                                    )
                                    ->limit(3)
                                    ->get()
                        )
                        ->schema([
                            TextEntry::make(
                                'ranking'
                            )
                                ->label(
                                    'Ranking'
                                )
                                ->badge(),

                            TextEntry::make(
                                'mitra.nama_mitra'
                            )
                                ->label(
                                    'Mitra'
                                ),

                            TextEntry::make(
                                'mitra.kategori.kategori'
                            )
                                ->label(
                                    'Kategori'
                                )
                                ->placeholder('-'),

                            TextEntry::make(
                                'mitra.negara.nama_negara'
                            )
                                ->label(
                                    'Negara'
                                )
                                ->placeholder(
                                    'Indonesia'
                                ),

                            TextEntry::make(
                                'total_score'
                            )
                                ->label(
                                    'Total Score'
                                )
                                ->numeric(
                                    decimalPlaces: 4
                                ),
                        ])
                        ->columns(5),
                ]),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY
    |--------------------------------------------------------------------------
    */

    public static function getEloquentQuery(): Builder
    {
        /*
        | Jangan gunakan withCount('scores') di sini
        | karena kolom tabel sudah menggunakan:
        |
        | ->counts('scores')
        |
        | Jika keduanya digunakan, scores_count
        | dapat dibuat dua kali.
        */

        return parent::getEloquentQuery();
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public static function getRelations(): array
    {
        return [
            ScoresRelationManager::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PAGES
    |--------------------------------------------------------------------------
    */

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListMitraAwardPeriods::route('/'),

            'create' =>
                Pages\CreateMitraAwardPeriod::route(
                    '/create'
                ),

            'view' =>
                Pages\ViewMitraAwardPeriod::route(
                    '/{record}'
                ),

            'edit' =>
                Pages\EditMitraAwardPeriod::route(
                    '/{record}/edit'
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | DEFAULT BOBOT
    |--------------------------------------------------------------------------
    */

    protected static function defaultBobot(): array
    {
        $config =
            MitraAwardCalculator::defaultConfig();

        $result = [];

        foreach (
            ($config['bobot'] ?? []) as
            $kriteria => $weight
        ) {
            $result[] = [
                'kriteria' => $kriteria,

                'nilai' => round(
                    ((float) $weight) * 100,
                    2
                ),
            ];
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | DEFAULT SKALA
    |--------------------------------------------------------------------------
    */

    protected static function defaultSkala(): array
    {
        $config =
            MitraAwardCalculator::defaultConfig();

        $result = [];

        foreach (
            ($config['skala'] ?? []) as
            $kriteria => $scale
        ) {
            $thresholds = array_values(
                $scale['thresholds'] ?? []
            );

            $labels = array_values(
                $scale['labels'] ?? []
            );

            $thresholds = array_pad(
                $thresholds,
                4,
                null
            );

            $labels = array_pad(
                $labels,
                5,
                null
            );

            $result[] = [
                'kriteria' => $kriteria,

                'type' =>
                    $scale['type']
                    ?? 'count',

                'thresholds' =>
                    $thresholds,

                'labels' =>
                    $labels,
            ];
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIGURATION FORM DATA
    |--------------------------------------------------------------------------
    */

    protected static function configurationFormData(
        MitraAwardPeriod $record
    ): array {
        $default =
            MitraAwardCalculator::defaultConfig();

        $config =
            $record->konfigurasi_penilaian;

        if (
            ! is_array($config) ||
            empty($config)
        ) {
            $config = $default;
        }

        /*
        |--------------------------------------------------------------------------
        | KOMPATIBILITAS DATA LAMA
        |--------------------------------------------------------------------------
        |
        | Jika konfigurasi lama menggunakan:
        |
        | weights / scales
        |
        | konversikan ke:
        |
        | bobot / skala
        |
        */

        if (
            isset($config['weights']) &&
            ! isset($config['bobot'])
        ) {
            $config['bobot'] =
                $config['weights'];

            unset(
                $config['weights']
            );
        }

        if (
            isset($config['scales']) &&
            ! isset($config['skala'])
        ) {
            $config['skala'] =
                $config['scales'];

            unset(
                $config['scales']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | MERGE DEFAULT
        |--------------------------------------------------------------------------
        */

        $config = array_replace_recursive(
            $default,
            $config
        );

        /*
        |--------------------------------------------------------------------------
        | BOBOT
        |--------------------------------------------------------------------------
        */

        $bobot = [];

        foreach (
            ($config['bobot'] ?? [])
            as $kriteria => $weight
        ) {
            $bobot[] = [
                'kriteria' => $kriteria,

                'nilai' => round(
                    ((float) $weight) * 100,
                    2
                ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | SKALA
        |--------------------------------------------------------------------------
        */

        $skala = [];

        foreach (
            ($config['skala'] ?? [])
            as $kriteria => $scale
        ) {
            $thresholds = array_values(
                $scale['thresholds'] ?? []
            );

            $labels = array_values(
                $scale['labels'] ?? []
            );

            $thresholds = array_pad(
                $thresholds,
                4,
                null
            );

            $labels = array_pad(
                $labels,
                5,
                null
            );

            $skala[] = [
                'kriteria' => $kriteria,

                'type' =>
                    $scale['type']
                    ?? 'count',

                'thresholds' =>
                    $thresholds,

                'labels' =>
                    $labels,
            ];
        }

        return [
            'bobot' => $bobot,
            'skala' => $skala,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE CONFIGURATION
    |--------------------------------------------------------------------------
    */

    protected static function normalizeConfiguration(
        array $data
    ): array {
        $default =
            MitraAwardCalculator::defaultConfig();

        /*
        |--------------------------------------------------------------------------
        | BOBOT
        |--------------------------------------------------------------------------
        */

        $bobot = [];

        foreach (
            ($data['bobot'] ?? [])
            as $row
        ) {
            $key =
                $row['kriteria']
                ?? null;

            if (! $key) {
                continue;
            }

            $nilai =
                (float) (
                    $row['nilai']
                    ?? 0
                );

            /*
            | Form menggunakan persen.
            |
            | 5% = 0.05
            | 10% = 0.10
            */

            $bobot[$key] =
                $nilai / 100;
        }

        /*
        |--------------------------------------------------------------------------
        | PASTIKAN SEMUA KRITERIA ADA
        |--------------------------------------------------------------------------
        */

        foreach (
            ($default['bobot'] ?? [])
            as $key => $value
        ) {
            if (
                ! array_key_exists(
                    $key,
                    $bobot
                )
            ) {
                $bobot[$key] =
                    $value;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SKALA
        |--------------------------------------------------------------------------
        */

        $skala = [];

        foreach (
            ($data['skala'] ?? [])
            as $row
        ) {
            $key =
                $row['kriteria']
                ?? null;

            if (! $key) {
                continue;
            }

            $defaultScale =
                $default['skala'][$key]
                ?? [];

            $type =
                $row['type']
                ?? (
                    $defaultScale['type']
                    ?? 'count'
                );

            /*
            |--------------------------------------------------------------------------
            | THRESHOLDS
            |--------------------------------------------------------------------------
            */

            $thresholds = [];

            foreach (
                ($row['thresholds'] ?? [])
                as $value
            ) {
                if (
                    $value === null ||
                    $value === ''
                ) {
                    continue;
                }

                $thresholds[] =
                    (float) $value;
            }

            /*
            |--------------------------------------------------------------------------
            | LABELS
            |--------------------------------------------------------------------------
            */

            $labels = [];

            foreach (
                ($row['labels'] ?? [])
                as $value
            ) {
                if (
                    $value === null ||
                    $value === ''
                ) {
                    $labels[] = '';
                } else {
                    $labels[] =
                        (string) $value;
                }
            }

            $skala[$key] = [
                'type' =>
                    $type,

                'thresholds' =>
                    $thresholds,

                'labels' =>
                    $labels,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | PERTAHANKAN DEFAULT SKALA
        |--------------------------------------------------------------------------
        */

        foreach (
            ($default['skala'] ?? [])
            as $key => $scale
        ) {
            if (
                ! array_key_exists(
                    $key,
                    $skala
                )
            ) {
                $skala[$key] =
                    $scale;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | HASIL AKHIR
        |--------------------------------------------------------------------------
        |
        | Ini harus sama dengan format yang
        | digunakan MitraAwardCalculator:
        |
        | bobot
        | skala
        |
        */

        return [
            'bobot' => $bobot,
            'skala' => $skala,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CRITERIA UNIT
    |--------------------------------------------------------------------------
    */

    protected static function getCriteriaUnit(
        ?string $key
    ): string {
        if (! $key) {
            return '-';
        }

        return
            MitraAwardCalculator::CRITERIA_UNITS[
                $key
            ]
            ?? '-';
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL BOBOT
    |--------------------------------------------------------------------------
    */

    protected static function totalBobotHtml(
        mixed $state
    ): HtmlString {
        $total = 0;

        if (is_array($state)) {
            foreach (
                $state as $row
            ) {
                if (! is_array($row)) {
                    continue;
                }

                $total +=
                    (float) (
                        $row['nilai']
                        ?? 0
                    );
            }
        }

        $class =
            abs($total - 100) < 0.001
                ? 'text-success-600'
                : 'text-danger-600';

        $formatted =
            number_format(
                $total,
                2,
                ',',
                '.'
            );

        return new HtmlString(
            '<span class="font-bold ' .
            $class .
            '">' .
            $formatted .
            '%</span>'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCALE DESCRIPTION
    |--------------------------------------------------------------------------
    */

    protected static function scaleDescription(
        mixed $state
    ): HtmlString {
        if (! is_array($state)) {
            return new HtmlString('-');
        }

        $type =
            $state['type']
            ?? 'count';

        $thresholds =
            array_values(
                $state['thresholds']
                ?? []
            );

        $labels =
            array_values(
                $state['labels']
                ?? []
            );

        /*
        |--------------------------------------------------------------------------
        | DOCUMENT
        |--------------------------------------------------------------------------
        */

        if ($type === 'document') {
            return static::labelDescription(
                $labels
            );
        }

        /*
        |--------------------------------------------------------------------------
        | LIKERT
        |--------------------------------------------------------------------------
        */

        if ($type === 'likert') {
            return static::labelDescription(
                $labels
            );
        }

        /*
        |--------------------------------------------------------------------------
        | COUNT / MONEY
        |--------------------------------------------------------------------------
        */

        if (empty($thresholds)) {
            return new HtmlString('-');
        }

        $items = [];

        /*
        | Threshold pertama menghasilkan score 0.
        */

        if (
            isset($thresholds[0]) &&
            $thresholds[0] !== null &&
            $thresholds[0] !== ''
        ) {
            $items[] =
                '<div>' .
                '<span class="font-semibold">0:</span> ≤ ' .
                e(
                    (string) $thresholds[0]
                ) .
                '</div>';
        }

        /*
        | Threshold berikutnya menghasilkan score 1, 2, 3.
        */

        foreach (
            array_slice(
                $thresholds,
                1
            ) as $index => $threshold
        ) {
            if (
                $threshold === null ||
                $threshold === ''
            ) {
                continue;
            }

            $score =
                $index + 1;

            $items[] =
                '<div>' .
                '<span class="font-semibold">' .
                $score .
                ':</span> ≤ ' .
                e(
                    (string) $threshold
                ) .
                '</div>';
        }

        /*
        | Nilai di atas threshold terakhir
        | mendapatkan score 4.
        */

        $lastThreshold =
            end($thresholds);

        if (
            $lastThreshold !== false &&
            $lastThreshold !== null &&
            $lastThreshold !== ''
        ) {
            $items[] =
                '<div>' .
                '<span class="font-semibold">4:</span> > ' .
                e(
                    (string) $lastThreshold
                ) .
                '</div>';
        }

        return new HtmlString(
            empty($items)
                ? '-'
                : implode(
                    '',
                    $items
                )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LABEL DESCRIPTION
    |--------------------------------------------------------------------------
    */

    protected static function labelDescription(
        array $labels
    ): HtmlString {
        $items = [];

        foreach (
            $labels as $score => $label
        ) {
            if (
                $label === null ||
                $label === ''
            ) {
                continue;
            }

            $items[] =
                '<div>' .
                '<span class="font-semibold">' .
                $score .
                ':</span> ' .
                e(
                    (string) $label
                ) .
                '</div>';
        }

        return new HtmlString(
            empty($items)
                ? '-'
                : implode(
                    '',
                    $items
                )
        );
    }
}
