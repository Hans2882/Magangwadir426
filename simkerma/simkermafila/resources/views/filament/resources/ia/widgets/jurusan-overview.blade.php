<x-filament-widgets::widget class="fi-wi-stats-overview">
    <div style="margin-bottom: 1.5rem;">
        <x-filament::section
            icon="heroicon-o-academic-cap"
            icon-color="primary"
            compact
        >
            <x-slot name="heading">
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <span style="font-weight: 600;">Statistik Dokumen IA per Jurusan</span>
                    @php
                        $currYear = (string) date('Y');
                        $lastYear = (string) ((int) $currYear - 1);
                        $activeYear = $this->selectedYear ?: 'all';
                        $activeStatus = $this->selectedStatus ?: 'all';
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

                    @if ($activeStatus === 'aktif')
                        <x-filament::badge color="success" size="sm">
                            Status: Aktif
                        </x-filament::badge>
                    @elseif ($activeStatus === 'berakhir')
                        <x-filament::badge color="danger" size="sm">
                            Status: Berakhir
                        </x-filament::badge>
                    @else
                        <x-filament::badge color="gray" size="sm">
                            Status: Semua
                        </x-filament::badge>
                    @endif
                </div>
            </x-slot>

            <x-slot name="description">
                Sebaran capaian dokumen Implementation Arrangement (IA) di masing-masing jurusan Polinema
            </x-slot>

            <x-slot name="afterHeader">
                <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                    {{-- Status Filter Buttons --}}
                    <div style="display: inline-flex; align-items: center; gap: 0.25rem;">
                        <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-right: 0.25rem;">Status:</span>
                        <x-filament::button
                            size="xs"
                            :color="$activeStatus === 'all' ? 'primary' : 'gray'"
                            :outlined="$activeStatus !== 'all'"
                            wire:click="setStatus('all')"
                        >
                            Semua
                        </x-filament::button>

                        <x-filament::button
                            size="xs"
                            :color="$activeStatus === 'aktif' ? 'success' : 'gray'"
                            :outlined="$activeStatus !== 'aktif'"
                            wire:click="setStatus('aktif')"
                        >
                            Aktif
                        </x-filament::button>

                        <x-filament::button
                            size="xs"
                            :color="$activeStatus === 'berakhir' ? 'danger' : 'gray'"
                            :outlined="$activeStatus !== 'berakhir'"
                            wire:click="setStatus('berakhir')"
                        >
                            Berakhir
                        </x-filament::button>
                    </div>

                    {{-- Year Filter Buttons & Select --}}
                    <div style="display: inline-flex; align-items: center; gap: 0.25rem;">
                        <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-right: 0.25rem;">Tahun:</span>
                        <x-filament::button
                            size="xs"
                            :color="$activeYear === 'all' ? 'primary' : 'gray'"
                            :outlined="$activeYear !== 'all'"
                            wire:click="setYear('all')"
                        >
                            Semua
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

                    <div style="min-width: 170px;">
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

    {{-- Cards Grid --}}
    {{ $this->content }}
</x-filament-widgets::widget>
