<x-filament-widgets::widget class="fi-wi-stats-overview">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                Statistik Pengguna & Dokumen Kerma
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Pilih tahun pada dropdown untuk melihat jumlah Dokumen Kerma tahun lalu atau tahun tertentu
            </p>
        </div>
        <div class="flex items-center gap-2">
            <label for="filter-year" class="text-xs font-medium text-gray-600 dark:text-gray-300">
                Tahun Dokumen:
            </label>
            <select
                id="filter-year"
                wire:model.live="selectedYear"
                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-900 shadow-sm transition hover:border-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
            >
                @foreach($this->getAvailableYears() as $year => $label)
                    <option value="{{ $year }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{ $this->content }}
</x-filament-widgets::widget>
