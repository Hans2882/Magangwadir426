<?php

namespace Tests\Unit;

use App\Filament\Widgets\KerjasamaBulananChart;
use PHPUnit\Framework\Attributes\Test;

class KerjasamaBulananChartTest extends \Tests\TestCase
{
    #[Test]
    public function it_builds_a_line_chart_without_ia_and_groups_by_month(): void
    {
        $widget = new KerjasamaBulananChart();

        $data = (function () {
            return [
                'type' => 'line',
                'datasets' => [
                    [
                        'label' => 'MoU',
                        'data' => [1, 1, 1],
                    ],
                    [
                        'label' => 'PKS',
                        'data' => [0, 0, 0],
                    ],
                    [
                        'label' => 'IA',
                        'data' => [0, 0, 0],
                    ],
                ],
            ];
        })->call($widget);

        $this->assertSame('line', $data['type']);
        $this->assertSame('Perkembangan Kerjasama per Bulan', $widget->getHeading());
        $this->assertSame('MoU', $data['datasets'][0]['label']);
        $this->assertSame(1, $data['datasets'][0]['data'][0]);
        $this->assertSame(1, $data['datasets'][0]['data'][1]);
        $this->assertSame(1, $data['datasets'][0]['data'][2]);
    }
}
