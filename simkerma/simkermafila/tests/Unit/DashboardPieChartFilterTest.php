<?php

namespace Tests\Unit;

use App\Filament\Widgets\JenisKerjasamaChart;
use App\Filament\Widgets\StatusKerjasamaChart;
use PHPUnit\Framework\TestCase;

class DashboardPieChartFilterTest extends TestCase
{
    public function test_jenis_chart_resolve_year_range_from_preset_matches_dashboard_filter(): void
    {
        $widget = new class extends JenisKerjasamaChart {
            public function exposeResolveYearRangeFromPreset(string $preset, ?string $startYear = null, ?string $endYear = null): array
            {
                return $this->resolveYearRangeFromPreset($preset, $startYear, $endYear);
            }
        };

        $this->assertSame([now()->year, now()->year], $widget->exposeResolveYearRangeFromPreset('this_year', null, null));
        $this->assertSame([now()->year - 1, now()->year], $widget->exposeResolveYearRangeFromPreset('last_1_year', null, null));
        $this->assertSame([now()->year - 5, now()->year], $widget->exposeResolveYearRangeFromPreset('last_5_years', null, null));
        $this->assertSame([2000, now()->year], $widget->exposeResolveYearRangeFromPreset('all_time', null, null));
        $this->assertSame([2024, 2026], $widget->exposeResolveYearRangeFromPreset('custom', '2024', '2026'));
    }

    public function test_status_chart_resolve_year_range_from_preset_matches_dashboard_filter(): void
    {
        $widget = new class extends StatusKerjasamaChart {
            public function exposeResolveYearRangeFromPreset(string $preset, ?string $startYear = null, ?string $endYear = null): array
            {
                return $this->resolveYearRangeFromPreset($preset, $startYear, $endYear);
            }
        };

        $this->assertSame([now()->year, now()->year], $widget->exposeResolveYearRangeFromPreset('this_year', null, null));
        $this->assertSame([now()->year - 10, now()->year], $widget->exposeResolveYearRangeFromPreset('last_10_years', null, null));
        $this->assertSame([2022, 2023], $widget->exposeResolveYearRangeFromPreset('custom', '2022', '2023'));
    }
}
