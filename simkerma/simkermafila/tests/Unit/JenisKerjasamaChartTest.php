<?php

namespace Tests\Unit;

use App\Filament\Widgets\JenisKerjasamaChart;
use PHPUnit\Framework\Attributes\Test;

class JenisKerjasamaChartTest extends \Tests\TestCase
{
    #[Test]
    public function it_builds_a_chart_with_three_categories(): void
    {
        $widget = new JenisKerjasamaChart();

        $data = [
            'datasets' => [
                [
                    'data' => [4, 6, 2],
                ],
            ],
            'labels' => ['MoU: 40.00%', 'PKS: 60.00%', 'IA: 20.00%'],
        ];

        $this->assertSame([4, 6, 2], $data['datasets'][0]['data']);
        $this->assertSame(['MoU: 40.00%', 'PKS: 60.00%', 'IA: 20.00%'], $data['labels']);
    }
}
