<x-filament-widgets::widget class="fi-wi-stats-overview">
    <div style="margin-bottom: 1.5rem;">
        <x-filament::section
            icon="heroicon-o-calendar-days"
            icon-color="primary"
            compact
        >
            <x-slot name="heading">
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <span>Statistik Pengguna & Dokumen Kerma</span>
                    @php
                        $currYear = (string) date('Y');
                        $lastYear = (string) ((int) $currYear - 1);
                        $activeYear = $this->selectedYear ?: $currYear;
                    @endphp
                    @if ($activeYear === 'all')
                        <x-filament::badge color="info" size="sm">
                            Semua Tahun
                        </x-filament::badge>
                    @elseif ($activeYear === $currYear)
                        <x-filament::badge color="success" size="sm">
                            Tahun Berjalan ({{ $activeYear }})
                        </x-filament::badge>
                    @else
                        <x-filament::badge color="warning" size="sm">
                            Tahun {{ $activeYear }}
                        </x-filament::badge>
                    @endif
                </div>
            </x-slot>

            <x-slot name="description">
                Pilih tahun pada filter untuk menganalisis rujukan Dokumen Kerma (MoU/PKS/IA) periode tertentu
            </x-slot>

            <x-slot name="afterHeader">
                <div style="display: flex; align-items: center; gap: 0.625rem; flex-wrap: wrap;">
                    {{-- Quick preset buttons using native Filament buttons --}}
                    <div style="display: inline-flex; align-items: center; gap: 0.25rem;">
                        <x-filament::button
                            size="xs"
                            :color="$activeYear === $currYear ? 'primary' : 'gray'"
                            :outlined="$activeYear !== $currYear"
                            wire:click="setYear('{{ $currYear }}')"
                        >
                            Tahun Ini
                        </x-filament::button>

                        <x-filament::button
                            size="xs"
                            :color="$activeYear === $lastYear ? 'primary' : 'gray'"
                            :outlined="$activeYear !== $lastYear"
                            wire:click="setYear('{{ $lastYear }}')"
                        >
                            Tahun Lalu
                        </x-filament::button>

                        <x-filament::button
                            size="xs"
                            :color="$activeYear === 'all' ? 'primary' : 'gray'"
                            :outlined="$activeYear !== 'all'"
                            wire:click="setYear('all')"
                        >
                            Semua
                        </x-filament::button>
                    </div>

                    {{-- Native Filament Input Select with Calendar Icon --}}
                    <div style="min-width: 190px;">
                        <x-filament::input.wrapper
                            :prefix-icon="'heroicon-m-calendar'"
                            wire:target="selectedYear, setYear"
                        >
                            <x-filament::input.select wire:model.live="selectedYear">
                                @foreach($this->getAvailableYears() as $year => $label)
                                    <option value="{{ $year }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                </div>
            </x-slot>
        </x-filament::section>
    </div>

    {{-- Stats Cards Grid --}}
    {{ $this->content }}
</x-filament-widgets::widget>
