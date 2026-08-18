<?php

namespace Tests\Feature;

use App\Filament\Resources\IaResource;
use App\Filament\Resources\MoaResource;
use App\Filament\Resources\MouResource;
use App\Filament\Resources\PksSpkResource;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Tests\TestCase;

class TopDownJenisPengajuanTest extends TestCase
{
    public function test_mou_pks_ia_and_moa_include_topdown_option(): void
    {
        $resources = [
            MouResource::class,
            PksSpkResource::class,
            IaResource::class,
            MoaResource::class,
        ];

        foreach ($resources as $resource) {
            $schema = $resource::form(Schema::make());
            $components = $schema->getComponents();

            $select = collect($components)
                ->first(fn ($component) => $component instanceof Select && $component->getName() === 'jenis_pengajuan');

            $this->assertInstanceOf(Select::class, $select);
            $this->assertArrayHasKey('TopDown', $select->getOptions());
        }
    }
}
