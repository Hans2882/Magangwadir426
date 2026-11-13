<x-filament-panels::page>
    <div class="mb-8">
        <h2 class="text-xl font-bold mb-4">Data Kerjasama Baru</h2>
        {{ $this->table }}
    </div>

    <div wire:ignore>
        <h2 class="text-xl font-bold mb-4">Dokumen Dalam Proses</h2>
        @livewire(\App\Livewire\TrackingDraftTable::class, [], key('tracking-draft-table-component'))
    </div>
</x-filament-panels::page>