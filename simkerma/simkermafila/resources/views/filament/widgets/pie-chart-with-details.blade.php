@php
    use Filament\Support\Facades\FilamentView;

    $color = $this->getColor();
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $filters = $this->getChartFilters();
    $details = $this->getChartDetails();
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <x-filament::section :description="$description" :heading="$heading">
        {{-- Filter controls --}}
        <x-slot name="afterHeader">
            <div class="flex flex-wrap items-end gap-2 sm:-my-2">
                {{-- Preset dropdown --}}
                <x-filament::input.wrapper inline-prefix wire:target="filter" class="w-max">
                    <x-filament::input.select inline-prefix wire:model.live="filter">
                        @foreach ($filters as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                {{-- Custom year inputs (shown only when filter = 'custom') --}}
                @if ($this->filter === 'custom')
                    <div class="flex items-center gap-1">
                        <input
                            type="number"
                            wire:model.live.debounce.500ms="customStartYear"
                            placeholder="Tahun Awal"
                            class="fi-input block w-24 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <span class="text-xs text-gray-500 dark:text-gray-400">s/d</span>
                        <input
                            type="number"
                            wire:model.live.debounce.500ms="customEndYear"
                            placeholder="Tahun Akhir"
                            class="fi-input block w-24 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                @endif
            </div>
        </x-slot>

        <div
            @if ($pollingInterval = $this->getPollingInterval())
                wire:poll.{{ $pollingInterval }}="updateChartData"
            @endif
        >
            {{-- Chart canvas --}}
            <div
                @if (FilamentView::hasSpaMode())
                    x-load="visible"
                @else
                    x-load
                @endif
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                x-data="chart({
                            cachedData: @js($this->getCachedData()),
                            options: @js($this->getOptions()),
                            type: @js($this->getType()),
                        })"
                @class([
                    match ($color) {
                        'gray' => null,
                        default => 'fi-color-custom',
                    },
                    is_string($color) ? "fi-color-{$color}" : null,
                ])
            >
                <canvas
                    x-ref="canvas"
                    @if ($maxHeight = $this->getMaxHeight())
                        style="max-height: {{ $maxHeight }}"
                    @endif
                ></canvas>

                <span
                    x-ref="backgroundColorElement"
                    @class([
                        match ($color) {
                            'gray' => 'text-gray-100 dark:text-gray-800',
                            default => 'text-custom-50 dark:text-custom-400/10',
                        },
                    ])
                    @style([
                        \Filament\Support\get_color_css_variables(
                            $color,
                            shades: [50, 400],
                            alias: 'widgets::chart-widget.background',
                        ) => $color !== 'gray',
                    ])
                ></span>

                <span
                    x-ref="borderColorElement"
                    @class([
                        match ($color) {
                            'gray' => 'text-gray-400',
                            default => 'text-custom-500 dark:text-custom-400',
                        },
                    ])
                    @style([
                        \Filament\Support\get_color_css_variables(
                            $color,
                            shades: [400, 500],
                            alias: 'widgets::chart-widget.border',
                        ) => $color !== 'gray',
                    ])
                ></span>

                <span
                    x-ref="gridColorElement"
                    class="text-gray-200 dark:text-gray-800"
                ></span>

                <span
                    x-ref="textColorElement"
                    class="text-gray-500 dark:text-gray-400"
                ></span>
            </div>
        </div>

        {{-- Prodi detail section --}}
        @if (!empty($details))
            <div style="margin-top: 1.5rem; border-top: 1px solid rgba(156, 163, 175, 0.2); padding-top: 1rem;">
                <p style="font-size: 0.75rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; color: rgb(107, 114, 128); margin-bottom: 0.75rem;">
                    Detail Prodi
                </p>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @foreach ($details as $detail)
                        <x-filament::section collapsible collapsed compact>
                            <x-slot name="heading">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span>{{ $detail['label'] }}</span>
                                    <x-filament::badge color="primary" size="sm">
                                        {{ $detail['count'] }}
                                    </x-filament::badge>
                                </div>
                            </x-slot>

                            @if (!empty($detail['prodi']))
                                <ul style="display: flex; flex-direction: column; gap: 0.25rem; font-size: 0.875rem; color: rgb(75, 85, 99);">
                                    @foreach ($detail['prodi'] as $prodi)
                                        <li style="display: flex; justify-content: space-between; align-items: center;">
                                            <span>{{ $prodi['name'] }}</span>
                                            <span style="font-weight: 600; color: rgb(55, 65, 81);">{{ $prodi['count'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p style="font-size: 0.875rem; color: rgb(107, 114, 128);">Tidak ada data prodi.</p>
                            @endif
                        </x-filament::section>
                    @endforeach
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
