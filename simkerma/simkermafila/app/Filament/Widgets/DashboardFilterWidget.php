<?php

namespace App\Filament\Widgets;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Widgets\Widget;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Attributes\Url;

class DashboardFilterWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.widgets.dashboard-filter-widget';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public ?string $preset = 'this_year';
    
    public ?string $startYear = null;

    public ?string $endYear = null;

    public function mount(): void
    {
        $this->form->fill([
            'preset' => $this->preset,
            'startYear' => $this->startYear ?? now()->year,
            'endYear' => $this->endYear ?? now()->year,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Filter Data')
                ->schema([
                    Select::make('preset')
                        ->label('Rentang Waktu')
                        ->options([
                            'this_year' => 'Tahun Ini',
                            'last_1_year' => '1 Tahun Terakhir',
                            'last_5_years' => '5 Tahun Terakhir',
                            'last_10_years' => '10 Tahun Terakhir',
                            'all_time' => 'Semua Waktu',
                            'custom' => 'Tahun Kustom (Pilih Manual)',
                        ])
                        ->live()
                        ->afterStateUpdated(function ($state) {
                            $this->preset = $state;
                            $this->dispatch('filter-updated', preset: $this->preset, startYear: $this->startYear, endYear: $this->endYear);
                        }),
                    TextInput::make('startYear')
                        ->label('Tahun Awal')
                        ->numeric()
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($state) {
                            $this->startYear = $state;
                            $this->dispatch('filter-updated', preset: $this->preset, startYear: $this->startYear, endYear: $this->endYear);
                        })
                        ->visible(fn (Get $get) => $get('preset') === 'custom'),
                    TextInput::make('endYear')
                        ->label('Tahun Akhir')
                        ->numeric()
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($state) {
                            $this->endYear = $state;
                            $this->dispatch('filter-updated', preset: $this->preset, startYear: $this->startYear, endYear: $this->endYear);
                        })
                        ->visible(fn (Get $get) => $get('preset') === 'custom'),
                ])->columns(3),
        ]);
    }
}
