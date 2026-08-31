<?php

namespace Tests\Unit;

use App\Filament\Widgets\JenisKerjasamaChart;
use PHPUnit\Framework\Attributes\Test;

class JenisKerjasamaChartTest extends \Tests\TestCase
{
    #[Test]
    public function it_exposes_year_and_range_filters_for_the_pie_chart(): void
    {
        $widget = new JenisKerjasamaChart();
        $widget->mount();

        $reflection = new \ReflectionMethod(JenisKerjasamaChart::class, 'getFilters');
        $reflection->setAccessible(true);
        $filters = $reflection->invoke($widget);

        $this->assertSame((string) now()->year, $widget->filter);
        $this->assertArrayHasKey((string) now()->year, $filters);
        $this->assertArrayHasKey((string) now()->year . '-' . (string) now()->year, $filters);
    }
}
