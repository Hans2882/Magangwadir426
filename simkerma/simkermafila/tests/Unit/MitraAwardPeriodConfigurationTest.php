<?php

namespace Tests\Unit;

use App\Filament\Resources\MitraAwardPeriodResource;
use App\Filament\Resources\MitraAwardPeriodResource\Pages\ViewMitraAwardPeriod;
use App\Services\MitraAwardCalculator;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use ReflectionMethod;
use Tests\TestCase;

class MitraAwardPeriodConfigurationTest extends TestCase
{
    public function test_period_view_header_has_weight_edit_and_delete_actions(): void
    {
        $page = (new \ReflectionClass(ViewMitraAwardPeriod::class))
            ->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(ViewMitraAwardPeriod::class, 'getHeaderActions');
        $actions = $method->invoke($page);

        $this->assertSame(
            ['konfigurasi_penilaian', 'edit', 'delete'],
            array_map(
                fn (Action $action): string => $action->getName(),
                $actions
            )
        );
    }

    public function test_it_builds_the_period_form_schema(): void
    {
        $schema = Schema::make();

        MitraAwardPeriodResource::form($schema);

        $this->assertInstanceOf(Schema::class, $schema);
    }

    public function test_period_dates_use_native_inputs_that_allow_direct_entry(): void
    {
        $schema = Schema::make();
        MitraAwardPeriodResource::form($schema);

        $datePickers = collect(
            $schema->getComponents()[0]->getDefaultChildComponents()
        )->filter(fn ($component): bool => $component instanceof DatePicker);

        $this->assertCount(2, $datePickers);
        $this->assertTrue(
            $datePickers->every(fn (DatePicker $datePicker): bool => $datePicker->isNative())
        );
    }

    public function test_published_wizard_asset_matches_the_installed_filament_api(): void
    {
        $publishedAsset = public_path(
            'js/filament/schemas/components/wizard.js'
        );
        $installedAsset = base_path(
            'vendor/filament/schemas/dist/components/wizard.js'
        );

        $this->assertSame(
            hash_file('sha256', $installedAsset),
            hash_file('sha256', $publishedAsset)
        );
    }

    public function test_it_normalizes_displayed_criterion_labels_to_scale_keys(): void
    {
        $method = new ReflectionMethod(
            MitraAwardPeriodResource::class,
            'normalizeConfiguration'
        );

        $configuration = $method->invoke(null, [
            'skala' => [
                'magang' => [
                    'kriteria' => MitraAwardCalculator::CRITERIA_LABELS['magang'],
                    'type' => 'count',
                    'thresholds' => [0, 2, 4, 6],
                    'labels' => [],
                ],
            ],
        ]);

        $this->assertSame(
            [
                'type' => 'count',
                'thresholds' => [0.0, 2.0, 4.0, 6.0],
                'labels' => [],
            ],
            $configuration['skala']['magang']
        );
        $this->assertArrayNotHasKey(
            MitraAwardCalculator::CRITERIA_LABELS['magang'],
            $configuration['skala']
        );
    }
}
