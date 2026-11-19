<x-filament-widgets::widget class="fi-wi-stats-overview">
    <div style="margin-bottom: 1.5rem;">
        <x-filament::section
            icon="heroicon-o-academic-cap"
            icon-color="primary"
            compact
        >
            <x-slot name="heading">
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <span style="font-weight: 600;">Statistik Pelaporan Kegiatan per Jurusan</span>
                    @php
                        $currYear = (string) date('Y');
                        $lastYear = (string) ((int) $currYear - 1);
                        $activeYear = $this->selectedYear ?: 'all';
                        $activeSort = $this->sortBy ?: 'desc';
                    @endphp
                    @if ($activeYear === 'all')
                        <x-filament::badge color="info" size="sm">
                            Semua Tahun (Akumulasi)
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

                    @if ($activeSort === 'desc')
                        <x-filament::badge color="primary" size="sm">
                            Urutan: Terbanyak
                        </x-filament::badge>
                    @elseif ($activeSort === 'asc')
                        <x-filament::badge color="warning" size="sm">
                            Urutan: Paling Sedikit
                        </x-filament::badge>
                    @elseif ($activeSort === 'name_asc')
                        <x-filament::badge color="gray" size="sm">
                            Urutan: A - Z
                        </x-filament::badge>
                    @endif
                </div>
            </x-slot>

            <x-slot name="description">
                Sebaran capaian dokumen Pelaporan Kegiatan (Case Study & Press Release) di masing-masing jurusan Polinema
            </x-slot>

            <x-slot name="afterHeader">
                <div style="display: flex; align-items: center; gap: 0.625rem; flex-wrap: wrap;">
                    {{-- Sort Filter Buttons --}}
                    <div style="display: inline-flex; align-items: center; gap: 0.25rem;">
                        <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-right: 0.25rem;">Urutan:</span>
                        <x-filament::button
                            size="xs"
                            :color="$activeSort === 'desc' ? 'primary' : 'gray'"
                            :outlined="$activeSort !== 'desc'"
                            wire:click="setSort('desc')"
                            icon="heroicon-m-bars-arrow-down"
                        >
                            Terbanyak
                        </x-filament::button>

                        <x-filament::button
                            size="xs"
                            :color="$activeSort === 'asc' ? 'primary' : 'gray'"
                            :outlined="$activeSort !== 'asc'"
                            wire:click="setSort('asc')"
                            icon="heroicon-m-bars-arrow-up"
                        >
                            Paling Sedikit
                        </x-filament::button>

                        <x-filament::button
                            size="xs"
                            :color="$activeSort === 'name_asc' ? 'primary' : 'gray'"
                            :outlined="$activeSort !== 'name_asc'"
                            wire:click="setSort('name_asc')"
                        >
                            A - Z
                        </x-filament::button>
                    </div>

                    {{-- Quick preset buttons --}}
                    <div style="display: inline-flex; align-items: center; gap: 0.25rem;">
                        <x-filament::button
                            size="xs"
                            :color="$activeYear === 'all' ? 'primary' : 'gray'"
                            :outlined="$activeYear !== 'all'"
                            wire:click="setYear('all')"
                        >
                            Semua Tahun
                        </x-filament::button>

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
                    </div>

                    {{-- Year Select Dropdown --}}
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

    {{-- Cards Grid Rendered by Filament --}}
    {{ $this->content }}
</x-filament-widgets::widget>
