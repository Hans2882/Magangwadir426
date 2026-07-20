<?php

namespace App\Filament\Resources\IaResource\Pages;

use App\Filament\Resources\IaResource;
use Filament\Resources\Pages\ViewRecord;

class ViewIa extends ViewRecord
{
    protected static string $resource = IaResource::class;


    protected function getHeaderActions(): array
    {
        return [

            \Filament\Actions\EditAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ];
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        $url = $this->getResource()::getUrl('index');
        return new \Illuminate\Support\HtmlString('
            <div style="margin-top: 0.5rem;">
                <a href="' . $url . '" style="display: inline-flex; align-items: center; gap: 4px; font-size: 14px; font-weight: 500; color: #eab308; text-decoration: none; padding: 4px 8px; border-radius: 6px; background: rgba(234, 179, 8, 0.1);">
                    <svg style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Kembali
                </a>
            </div>
        ');
    }

}
