<?php

namespace Tests\Unit;

use App\Filament\Resources\MitraAwardPeriodResource\RelationManagers\ScoresRelationManager;
use App\Models\MitraAwardPeriod;
use App\Services\MitraAwardCalculator;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

class MitraAwardScoreLevelSelectionTest extends TestCase
{
    public function test_score_wizard_allows_direct_navigation_between_steps(): void
    {
        $manager = $this->makeRelationManager();
        $schema = $manager->form(Schema::make());
        $components = (new ReflectionProperty(Schema::class, 'components'))
            ->getValue($schema);
        $wizard = $components[0];

        $this->assertInstanceOf(Wizard::class, $wizard);
        $this->assertTrue($wizard->isSkippable());
    }

    public function test_score_level_choices_follow_the_period_thresholds(): void
    {
        $manager = $this->makeRelationManager();
        $method = new ReflectionMethod($manager, 'scoreLevelOptions');

        $options = $method->invoke($manager, 'magang');

        $this->assertSame('0', $options[0]);
        $this->assertSame('1–2', $options[1]);
        $this->assertSame('3–4', $options[2]);
        $this->assertSame('> 6', $options[4]);
    }

    public function test_default_count_choices_show_only_the_actual_values(): void
    {
        $manager = $this->makeRelationManager([
            'magang' => [
                'type' => 'count',
                'thresholds' => [0, 1, 2, 3],
            ],
        ]);
        $method = new ReflectionMethod($manager, 'scoreLevelOptions');

        $this->assertSame(
            [0 => '0', 1 => '1', 2 => '2', 3 => '3', 4 => '> 3'],
            $method->invoke($manager, 'magang')
        );
    }

    public function test_requested_money_aspects_show_exact_rupiah_ranges(): void
    {
        $manager = $this->makeRelationManager(
            MitraAwardCalculator::defaultConfig()['skala']
        );
        $method = new ReflectionMethod($manager, 'scoreLevelOptions');

        $expected = [
            'penelitian_cash' => [
                'Rp0',
                'Rp1 – Rp2.000.000',
                'Rp2.000.001 – Rp5.000.000',
                'Rp5.000.001 – Rp10.000.000',
                '≥ Rp10.000.001',
            ],
            'penelitian_kind' => [
                'Rp0',
                'Rp1 – Rp4.000.000',
                'Rp4.000.001 – Rp10.000.000',
                'Rp10.000.001 – Rp20.000.000',
                '≥ Rp20.000.001',
            ],
            'kajian_tenaga_ahli' => [
                'Rp0',
                'Rp1 – Rp20.000.000',
                'Rp20.000.001 – Rp50.000.000',
                'Rp50.000.001 – Rp100.000.000',
                '≥ Rp100.000.001',
            ],
            'hibah_alat' => [
                'Rp0',
                'Rp1 – Rp20.000.000',
                'Rp20.000.001 – Rp50.000.000',
                'Rp50.000.001 – Rp100.000.000',
                '≥ Rp100.000.001',
            ],
        ];

        foreach ($expected as $criterion => $options) {
            $this->assertSame(
                $options,
                array_values($method->invoke($manager, $criterion)),
                "Unexpected options for {$criterion}."
            );
        }
    }

    public function test_reputation_and_network_expansion_use_requested_labels(): void
    {
        $manager = $this->makeRelationManager(
            MitraAwardCalculator::defaultConfig()['skala']
        );
        $method = new ReflectionMethod($manager, 'scoreLevelOptions');

        $this->assertSame([
            0 => 'Tidak Bereputasi',
            1 => 'Bereputasi Rendah',
            2 => 'Bereputasi Sedang',
            3 => 'Bereputasi Tinggi',
            4 => 'Bereputasi Sangat Tinggi',
        ], $method->invoke($manager, 'reputasi'));

        $this->assertSame([
            0 => 'Tidak Berpengaruh',
            1 => 'Berpengaruh Sedikit',
            2 => 'Berpengaruh Sedang',
            3 => 'Berpengaruh Banyak',
            4 => 'Berpengaruh Sangat Banyak',
        ], $method->invoke($manager, 'perluasan_jejaring'));
    }

    public function test_selected_levels_are_converted_to_compatible_stored_values(): void
    {
        $manager = $this->makeRelationManager();
        $method = new ReflectionMethod($manager, 'scoreValuesFromLevels');

        $values = $method->invoke($manager, [
            'magang_level' => 3,
            'penelitian_cash_level' => 4,
        ]);

        $this->assertSame(6, $values['magang']);
        $this->assertSame(301.0, $values['penelitian_cash']);
        $this->assertArrayNotHasKey('magang_level', $values);
        $this->assertArrayNotHasKey('penelitian_cash_level', $values);
    }

    public function test_editing_a_saved_score_restores_its_level_selection(): void
    {
        $manager = $this->makeRelationManager();
        $method = new ReflectionMethod($manager, 'prepareScoreFormData');

        $formData = $method->invoke($manager, [
            'magang' => 5,
            'penelitian_cash' => 250,
        ]);

        $this->assertSame(3, $formData['magang_level']);
        $this->assertSame(3, $formData['penelitian_cash_level']);
    }

    public function test_each_option_maps_to_a_value_with_the_same_calculated_score(): void
    {
        $manager = $this->makeRelationManager(
            MitraAwardCalculator::defaultConfig()['skala']
        );
        $valueMethod = new ReflectionMethod($manager, 'scoreValueForLevel');
        $calculator = app(MitraAwardCalculator::class);

        $scales = MitraAwardCalculator::defaultConfig()['skala'];

        foreach ([
            'magang',
            'penelitian_cash',
            'penelitian_kind',
            'kajian_tenaga_ahli',
            'hibah_alat',
        ] as $criterion) {
            $thresholds = $scales[$criterion]['thresholds'];

            foreach (range(0, 4) as $level) {
                $value = $valueMethod->invoke($manager, $criterion, $level);

                $this->assertSame(
                    $level,
                    $calculator->thresholdScore($value, $thresholds),
                    "{$criterion} level {$level} should retain its calculated score."
                );
            }
        }
    }

    private function makeRelationManager(?array $scales = null): ScoresRelationManager
    {
        $period = new MitraAwardPeriod;
        $period->setAttribute('konfigurasi_penilaian', [
            'skala' => $scales ?? [
                'magang' => [
                    'type' => 'count',
                    'thresholds' => [0, 2, 4, 6],
                ],
                'penelitian_cash' => [
                    'type' => 'money',
                    'thresholds' => [0, 100, 200, 300],
                ],
            ],
        ]);

        $manager = (new ReflectionClass(ScoresRelationManager::class))
            ->newInstanceWithoutConstructor();

        (new ReflectionProperty(RelationManager::class, 'ownerRecord'))
            ->setValue($manager, $period);

        return $manager;
    }
}
