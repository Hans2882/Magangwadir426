<x-filament-panels::page>
    <form wire:submit="create" style="display: flex; flex-direction: column; gap: 1.5rem;">
        {{ $this->form }}

        <div style="display: flex; align-items: center; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid rgba(156, 163, 175, 0.2);">
            <x-filament::button type="submit" size="lg" icon="heroicon-m-check">
                Simpan Dokumen
            </x-filament::button>

            <x-filament::button type="button" color="gray" size="lg" icon="heroicon-m-arrow-path" wire:click="createAnother">
                Simpan & Tambah Lainnya
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
