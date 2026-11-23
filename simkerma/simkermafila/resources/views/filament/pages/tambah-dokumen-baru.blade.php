<x-filament-panels::page>
    <style>
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>

    <form
        wire:submit="create"
        x-data="{ isSubmitting: false, isSubmittingAnother: false }"
        x-init="
            Livewire.hook('commit', ({ component, succeed, fail }) => {
                succeed(({ snapshot, effect }) => {
                    if (effect?.redirect) {
                        return;
                    }
                    isSubmitting = false;
                    isSubmittingAnother = false;
                });
                fail(() => {
                    isSubmitting = false;
                    isSubmittingAnother = false;
                });
            });
        "
        x-on:submit="isSubmitting = true"
        style="display: flex; flex-direction: column; gap: 1.5rem;"
    >
        {{ $this->form }}

        <div
            style="display: flex; align-items: center; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid rgba(156, 163, 175, 0.2);">
            {{-- Tombol Simpan Dokumen Utama --}}
            <x-filament::button
                type="submit"
                size="lg"
                x-bind:disabled="isSubmitting || isSubmittingAnother"
                x-bind:class="{ 'opacity-75 cursor-wait pointer-events-none': isSubmitting || isSubmittingAnother }"
            >
                {{-- Kondisi Normal --}}
                <span x-show="!isSubmitting" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                    <x-filament::icon icon="heroicon-m-check" style="width: 1.25rem; height: 1.25rem;" />
                    <span>Simpan Dokumen</span>
                </span>

                {{-- Kondisi Loading (Tetap berputar sampai halaman berpindah) --}}
                <span x-cloak x-show="isSubmitting" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                    <svg style="width: 1.25rem; height: 1.25rem; animation: spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Menyimpan Dokumen...</span>
                </span>
            </x-filament::button>

            {{-- Tombol Simpan & Tambah Lainnya --}}
            <x-filament::button
                type="button"
                color="gray"
                size="lg"
                wire:click="createAnother"
                x-on:click="isSubmittingAnother = true"
                x-bind:disabled="isSubmitting || isSubmittingAnother"
                x-bind:class="{ 'opacity-75 cursor-wait pointer-events-none': isSubmitting || isSubmittingAnother }"
            >
                {{-- Kondisi Normal --}}
                <span x-show="!isSubmittingAnother" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                    <x-filament::icon icon="heroicon-m-arrow-path" style="width: 1.25rem; height: 1.25rem;" />
                    <span>Simpan & Tambah Lainnya</span>
                </span>

                {{-- Kondisi Loading --}}
                <span x-cloak x-show="isSubmittingAnother" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                    <svg style="width: 1.25rem; height: 1.25rem; animation: spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Menyimpan...</span>
                </span>
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
