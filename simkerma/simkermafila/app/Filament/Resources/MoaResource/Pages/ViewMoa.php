<?php

namespace App\Filament\Resources\MoaResource\Pages;

use App\Filament\Resources\MoaResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMoa extends ViewRecord
{
    protected static string $resource = MoaResource::class;

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        $url = url()->previous() !== url()->current() ? url()->previous() : $this->getResource()::getUrl('index');
        $title = parent::getHeading();
        
        return new \Illuminate\Support\HtmlString('
            <div class="flex items-center gap-4">
                <a href="' . $url . '" class="inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold shadow-sm ring-1 ring-inset ring-gray-300 bg-white text-gray-900 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:hover:bg-white/10 transition">
                    <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Kembali
                </a>
                <span>' . $title . '</span>
            </div>
        ');
    }
}
