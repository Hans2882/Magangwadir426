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
        <x-slot name="headerEnd">
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
            <div class="mt-5 space-y-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Detail Prodi
                </p>
                @foreach ($details as $detail)
                    <div
                        x-data="{ open: false }"
                        class="rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40"
                    >
                        {{-- Header (clickable toggle) --}}
                        <button
                            type="button"
                            x-on:click="open = !open"
                            class="flex w-full items-center justify-between gap-3 rounded-lg p-3 text-left transition hover:bg-gray-100 dark:hover:bg-gray-700/40"
                        >
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ $detail['label'] }}
                                <span class="ml-1 inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-800/30 dark:text-primary-400">
                                    {{ $detail['count'] }}
                                </span>
                            </div>
                            <svg
                                class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200"
                                :class="{ 'rotate-180': open }"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        {{-- Collapsible prodi list --}}
                        <div
                            x-show="open"
                            x-collapse
                            class="border-t border-gray-200 px-3 pb-3 dark:border-gray-700"
                        >
                            @if (!empty($detail['prodi']))
                                <ul class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-300">
                                    @foreach ($detail['prodi'] as $prodi)
                                        <li class="flex items-center justify-between gap-3">
                                            <span>{{ $prodi['name'] }}</span>
                                            <span class="font-medium text-gray-700 dark:text-gray-200">{{ $prodi['count'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Tidak ada data prodi.</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
