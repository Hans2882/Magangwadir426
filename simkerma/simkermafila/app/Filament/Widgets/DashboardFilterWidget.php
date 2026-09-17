<?php

namespace App\Filament\Widgets;

use App\Models\MasterJurusan;
use App\Models\MasterProgramStudi;
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

    public ?string $jurusanId = null;

    public ?string $prodiId = null;

    public function mount(): void
    {
        $this->form->fill([
            'preset' => $this->preset,
            'startYear' => $this->startYear ?? now()->year,
            'endYear' => $this->endYear ?? now()->year,
            'jurusanId' => $this->jurusanId,
            'prodiId' => $this->prodiId,
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
                            $this->dispatchFilter();
                        }),
                    TextInput::make('startYear')
                        ->label('Tahun Awal')
                        ->numeric()
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($state) {
                            $this->startYear = $state;
                            $this->dispatchFilter();
                        })
                        ->visible(fn (Get $get) => $get('preset') === 'custom'),
                    TextInput::make('endYear')
                        ->label('Tahun Akhir')
                        ->numeric()
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($state) {
                            $this->endYear = $state;
                            $this->dispatchFilter();
                        })
                        ->visible(fn (Get $get) => $get('preset') === 'custom'),
                    Select::make('jurusanId')
                        ->label('Jurusan')
                        ->options(fn (): array => ['' => 'Semua Jurusan'] + MasterJurusan::query()
                            ->orderBy('nama_jurusan')
                            ->pluck('nama_jurusan', 'id')
                            ->all())
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state) {
                            $this->jurusanId = $state ?: null;
                            $this->dispatchFilter();
                        }),
                    Select::make('prodiId')
                        ->label('Prodi')
                        ->options(fn (): array => ['' => 'Semua Prodi'] + MasterProgramStudi::query()
                            ->orderBy('nama_prodi')
                            ->pluck('nama_prodi', 'id')
                            ->all())
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state) {
                            $this->prodiId = $state ?: null;
                            $this->dispatchFilter();
                        }),
                ])->columns(3),
        ]);
    }

    protected function dispatchFilter(): void
    {
        $this->dispatch(
            'filter-updated',
            preset: $this->preset,
            startYear: $this->startYear,
            endYear: $this->endYear,
            jurusanId: $this->jurusanId,
            prodiId: $this->prodiId,
        );
    }
}
