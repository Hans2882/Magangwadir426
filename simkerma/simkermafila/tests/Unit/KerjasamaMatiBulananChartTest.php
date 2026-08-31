<?php

namespace Tests\Unit;

use App\Filament\Widgets\KerjasamaMatiBulananChart;
use PHPUnit\Framework\Attributes\Test;

class KerjasamaMatiBulananChartTest extends \Tests\TestCase
{
    #[Test]
    public function it_parses_year_ranges_for_chart_filter(): void
    {
        $widget = new class extends KerjasamaMatiBulananChart {
            public function publicResolveFilterRange(string $filter): array
            {
                return $this->resolveFilterRange($filter);
            }
        };

        $this->assertSame([2022, 2026], $widget->publicResolveFilterRange('2022-2026'));
        $this->assertSame([2026, 2026], $widget->publicResolveFilterRange('2026'));
    }
}
