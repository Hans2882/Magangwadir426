<?php

namespace App\Filament\Resources\MitraAwardPeriods\Schemas;

use App\Services\MitraAwardCalculator;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;

class MitraAwardPeriodConfiguration
{
    /**
     * Daftar kriteria.
     */
    public static function schema(): array
    {
        $labels =
            MitraAwardCalculator::criteriaLabels();

        $units =
            MitraAwardCalculator::criteriaUnits();

        return [

            Section::make('Konfigurasi Bobot Penilaian')
                ->description(
                    'Atur bobot masing-masing aspek. Total bobot harus tepat 100%.'
                )
                ->schema([

                    Repeater::make('bobot')
                        ->label('Bobot Penilaian')
                        ->schema([

                            Grid::make(12)
                                ->schema([

                                    Placeholder::make('kriteria_label')
                                        ->label('Aspek Penilaian')
                                        ->content(
                                            fn (
                                                Get $get
                                            ) =>
                                                $labels[
                                                    $get('kriteria')
                                                ] ?? '-'
                                        )
                                        ->columnSpan(7),

                                    TextInput::make('nilai')
                                        ->label('Bobot (%)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->step(0.1)
                                        ->suffix('%')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(
                                            fn (
                                                Get $get,
                                                Set $set
                                            ) => self::updateTotal(
                                                $get,
                                                $set
                                            )
                                        )
                                        ->columnSpan(3),

                                    Placeholder::make('satuan')
                                        ->label('Satuan')
                                        ->content(
                                            fn (
                                                Get $get
                                            ) =>
                                                $units[
                                                    $get('kriteria')
                                                ] ?? '-'
                                        )
                                        ->columnSpan(2),
                                ]),

                            TextInput::make('kriteria')
                                ->hidden(),
                        ])
                        ->default(
                            self::defaultBobot()
                        )
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->columnSpanFull(),

                    Placeholder::make('total_bobot')
                        ->label('Total Bobot')
                        ->content(
                            fn (Get $get) =>
                                self::totalBobotHtml(
                                    $get('bobot')
                                )
                        )
                        ->columnSpanFull(),

                ])
                ->collapsible(),

            Section::make('Skala Penilaian')
                ->description(
                    'Atur jenis skala dan batas nilai untuk masing-masing aspek.'
                )
                ->schema([

                    Repeater::make('skala')
                        ->label('Skala')
                        ->schema([

                            TextInput::make('kriteria')
                                ->label('Kriteria')
                                ->disabled()
                                ->dehydrated()
                                ->formatStateUsing(
                                    fn (
                                        $state
                                    ) =>
                                        $labels[$state] ?? $state
                                )
                                ->columnSpan(4),

                            Select::make('type')
                                ->label('Jenis Skala')
                                ->options([
                                    'count' => 'Jumlah',
                                    'money' => 'Nominal / Uang',
                                    'likert' => 'Likert',
                                    'document' => 'Dokumen',
                                ])
                                ->required()
                                ->live()
                                ->columnSpan(3),

                            Placeholder::make('unit')
                                ->label('Satuan')
                                ->content(
                                    fn (
                                        Get $get
                                    ) =>
                                        $units[
                                            $get('kriteria')
                                        ] ?? '-'
                                )
                                ->columnSpan(2),

                            Placeholder::make('preview')
                                ->label('Preview')
                                ->content(
                                    fn (
                                        Get $get
                                    ) => self::scalePreview(
                                        $get('type'),
                                        $get('thresholds'),
                                        $get('labels')
                                    )
                                )
                                ->columnSpan(3),

                            TextInput::make('thresholds.0')
                                ->label('Batas Skor 0')
                                ->numeric()
                                ->required()
                                ->columnSpan(2),

                            TextInput::make('thresholds.1')
                                ->label('Batas Skor 1')
                                ->numeric()
                                ->required()
                                ->columnSpan(2),

                            TextInput::make('thresholds.2')
                                ->label('Batas Skor 2')
                                ->numeric()
                                ->required()
                                ->columnSpan(2),

                            TextInput::make('thresholds.3')
                                ->label('Batas Skor 3')
                                ->numeric()
                                ->required()
                                ->columnSpan(2),

                            Placeholder::make('score4')
                                ->label('Skor 4')
                                ->content(
                                    fn (
                                        Get $get
                                    ) => self::score4Text(
                                        $get('thresholds')
                                    )
                                )
                                ->columnSpan(4),

                            TextInput::make('labels.0')
                                ->label('Keterangan Skor 0')
                                ->visible(
                                    fn (
                                        Get $get
                                    ) => in_array(
                                        $get('type'),
                                        [
                                            'likert',
                                            'document',
                                        ],
                                        true
                                    )
                                )
                                ->columnSpan(6),

                            TextInput::make('labels.1')
                                ->label('Keterangan Skor 1')
                                ->visible(
                                    fn (
                                        Get $get
                                    ) => in_array(
                                        $get('type'),
                                        [
                                            'likert',
                                            'document',
                                        ],
                                        true
                                    )
                                )
                                ->columnSpan(6),

                            TextInput::make('labels.2')
                                ->label('Keterangan Skor 2')
                                ->visible(
                                    fn (
                                        Get $get
                                    ) => in_array(
                                        $get('type'),
                                        [
                                            'likert',
                                            'document',
                                        ],
                                        true
                                    )
                                )
                                ->columnSpan(6),

                            TextInput::make('labels.3')
                                ->label('Keterangan Skor 3')
                                ->visible(
                                    fn (
                                        Get $get
                                    ) => in_array(
                                        $get('type'),
                                        [
                                            'likert',
                                            'document',
                                        ],
                                        true
                                    )
                                )
                                ->columnSpan(6),

                            TextInput::make('labels.4')
                                ->label('Keterangan Skor 4')
                                ->visible(
                                    fn (
                                        Get $get
                                    ) => in_array(
                                        $get('type'),
                                        [
                                            'likert',
                                            'document',
                                        ],
                                        true
                                    )
                                )
                                ->columnSpan(6),

                        ])
                        ->default(
                            self::defaultSkala()
                        )
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->columnSpanFull(),

                ])
                ->collapsible(),
        ];
    }

    /**
     * Default bobot untuk Repeater.
     */
    protected static function defaultBobot(): array
    {
        $config =
            MitraAwardCalculator::defaultConfig();

        return collect($config['bobot'])
            ->map(
                fn (
                    $value,
                    $key
                ) => [
                    'kriteria' => $key,
                    'nilai' => round(
                        $value * 100,
                        2
                    ),
                ]
            )
            ->values()
            ->all();
    }

    /**
     * Default skala.
     */
    protected static function defaultSkala(): array
    {
        $config =
            MitraAwardCalculator::defaultConfig();

        return collect($config['skala'])
            ->map(
                fn (
                    $value,
                    $key
                ) => [
                    'kriteria' => $key,
                    'type' =>
                        $value['type'] ?? 'count',
                    'thresholds' =>
                        $value['thresholds']
                        ?? [0, 1, 2, 3],
                    'labels' =>
                        $value['labels'] ?? [],
                ]
            )
            ->values()
            ->all();
    }

    /**
     * Total bobot.
     */
    protected static function totalBobotHtml(
        ?array $items
    ): HtmlString {
        $total = collect($items ?? [])
            ->sum(
                fn ($item) =>
                    (float) ($item['nilai'] ?? 0)
            );

        $formatted =
            number_format(
                $total,
                2,
                ',',
                '.'
            );

        $valid =
            abs($total - 100) < 0.0001;

        if ($valid) {
            return new HtmlString(
                "<div class='text-lg font-bold text-success-600'>
                    Total Bobot: {$formatted}%
                    ✓
                </div>"
            );
        }

        return new HtmlString(
            "<div class='text-lg font-bold text-danger-600'>
                Total Bobot: {$formatted}%
                — harus 100%
            </div>"
        );
    }

    /**
     * Preview skala.
     */
    protected static function scalePreview(
        ?string $type,
        ?array $thresholds,
        ?array $labels
    ): HtmlString {
        $thresholds ??= [];

        $text = match ($type) {
            'money' =>
                'Rp0 → 0 | '
                . '≤ ' . self::money($thresholds[1] ?? 0)
                . ' → 1 | '
                . '≤ ' . self::money($thresholds[2] ?? 0)
                . ' → 2 | '
                . '≤ ' . self::money($thresholds[3] ?? 0)
                . ' → 3 | > batas → 4',

            'likert',
            'document' =>
                collect(range(0, 4))
                    ->map(
                        fn ($score) =>
                            "{$score}: "
                            . ($labels[$score] ?? '-')
                    )
                    ->implode(' | '),

            default =>
                '≤ '
                . ($thresholds[0] ?? 0)
                . ' → 0 | '
                . '≤ '
                . ($thresholds[1] ?? 0)
                . ' → 1 | '
                . '≤ '
                . ($thresholds[2] ?? 0)
                . ' → 2 | '
                . '≤ '
                . ($thresholds[3] ?? 0)
                . ' → 3 | > batas → 4',
        };

        return new HtmlString(
            '<div class="text-sm">'
            . e($text)
            . '</div>'
        );
    }

    /**
     * Text skor 4.
     */
    protected static function score4Text(
        ?array $thresholds
    ): string {
        return 'Lebih besar dari '
            . ($thresholds[3] ?? 0);
    }

    /**
     * Format uang.
     */
    protected static function money(
        float|int $value
    ): string {
        return 'Rp '
            . number_format(
                $value,
                0,
                ',',
                '.'
            );
    }

    /**
     * Update total bobot.
     */
    protected static function updateTotal(
        Get $get,
        Set $set
    ): void {
        // Placeholder dihitung realtime melalui Get.
        // Tidak perlu menyimpan total ke database.
    }

    /**
     * Validasi konfigurasi sebelum save.
     */
    public static function normalize(
        array $state
    ): array {
        $weights = [];

        foreach (
            ($state['bobot'] ?? []) as $item
        ) {
            if (
                empty($item['kriteria'])
            ) {
                continue;
            }

            $weights[
                $item['kriteria']
            ] = round(
                ((float) ($item['nilai'] ?? 0))
                / 100,
                6
            );
        }

        $scale = [];

        foreach (
            ($state['skala'] ?? []) as $item
        ) {
            if (
                empty($item['kriteria'])
            ) {
                continue;
            }

            $scale[
                $item['kriteria']
            ] = [
                'type' =>
                    $item['type'] ?? 'count',

                'thresholds' =>
                    array_values(
                        array_map(
                            'floatval',
                            $item['thresholds']
                            ?? [0, 1, 2, 3]
                        )
                    ),

                'labels' =>
                    $item['labels'] ?? [],
            ];
        }

        return [
            'bobot' => $weights,
            'skala' => $scale,
        ];
    }
}