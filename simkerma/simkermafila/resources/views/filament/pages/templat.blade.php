<x-filament-panels::page>
    @php
        $templatesData = $templates->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->judul,
                'file' => $item->file_name,
                'category' => $item->kategori,
                'lang' => $item->bahasa,
                'is_active' => (bool) $item->is_active,
                'size' => $item->file_size_formatted,
            ];
        })->values()->all();
    @endphp

    <div
        x-data="{
            search: '',
            items: {{ \Illuminate\Support\Js::from($templatesData) }},
            get matchCount() {
                if (!this.search.trim()) return this.items.length;
                const q = this.search.toLowerCase().trim();
                return this.items.filter(i => (i.title + ' ' + i.file + ' ' + i.category + ' ' + i.lang).toLowerCase().includes(q)).length;
            }
        }"
    >
        <style>
            .templat-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            @media (min-width: 768px) {
                .templat-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            .templat-card {
                padding: 1rem;
                border-radius: 0.875rem;
                border: 1px solid rgba(156, 163, 175, 0.25);
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.875rem;
                min-width: 0;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
                background-color: #ffffff;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .templat-card:hover {
                border-color: rgba(var(--primary-500, 59, 130, 246), 0.5);
                box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.08);
                transform: translateY(-1px);
            }
            :is(.dark) .templat-card {
                background-color: rgba(17, 24, 39, 0.7);
                border-color: rgba(255, 255, 255, 0.1);
            }
            :is(.dark) .templat-card:hover {
                background-color: rgba(31, 41, 55, 0.85);
                border-color: rgba(var(--primary-400, 96, 165, 250), 0.4);
            }
            .templat-card-inactive {
                border-style: dashed;
                border-color: rgba(245, 158, 11, 0.5);
                background-color: rgba(254, 243, 199, 0.25);
            }
            :is(.dark) .templat-card-inactive {
                border-color: rgba(245, 158, 11, 0.35);
                background-color: rgba(120, 53, 15, 0.15);
            }
            .templat-title {
                font-weight: 600;
                font-size: 0.875rem;
                line-height: 1.25rem;
                color: #111827;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            :is(.dark) .templat-title {
                color: #f9fafb;
            }
            .templat-filename {
                font-size: 0.75rem;
                line-height: 1rem;
                color: #6b7280;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            :is(.dark) .templat-filename {
                color: #9ca3af;
            }
        </style>

        {{-- Section Header with Live Search --}}
        <div style="margin-bottom: 1.25rem;">
            <x-filament::section compact>
                <x-slot name="heading">
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <span>Download Templat Dokumen Kerjasama</span>
                        <x-filament::badge color="primary" size="sm">
                            <span x-text="matchCount + ' Templat'">{{ count($templates) }} Templat</span>
                        </x-filament::badge>
                    </div>
                </x-slot>

                {{-- <x-slot name="description">
                    Unduh templat resmi MoU, PKS, Implementation Arrangement (IA), dan Laporan Pelaksanaan Kerja Sama.
                </x-slot> --}}

                <x-slot name="afterHeader">
                    <div style="min-width: 220px; max-width: 320px; width: 100%;">
                        <x-filament::input.wrapper :prefix-icon="'heroicon-m-magnifying-glass'">
                            <x-filament::input
                                type="search"
                                x-model="search"
                                placeholder="Cari templat..."
                            />
                        </x-filament::input.wrapper>
                    </div>
                </x-slot>
            </x-filament::section>
        </div>

        {{-- Responsive Grid Container --}}
        <div class="templat-grid">
            @foreach($templates as $item)
                <div
                    class="templat-card {{ ! $item->is_active ? 'templat-card-inactive' : '' }}"
                    x-show="!search.trim() || '{{ strtolower($item->judul . ' ' . $item->file_name . ' ' . $item->kategori . ' ' . $item->bahasa) }}'.includes(search.toLowerCase().trim())"
                    x-transition
                >
                    <div style="display: flex; align-items: center; gap: 0.75rem; min-width: 0; flex: 1 1 0%;">
                        {{-- Icon --}}
                        <div style="display: flex; height: 2.75rem; width: 2.75rem; flex-shrink: 0; align-items: center; justify-content: center; border-radius: 0.625rem; background-color: rgba(var(--primary-50, 239, 246, 255), 0.7);" class="text-primary-600 dark:bg-primary-950/50 dark:text-primary-400">
                            <x-filament::icon icon="heroicon-o-document-arrow-down" style="height: 1.5rem; width: 1.5rem;" />
                        </div>

                        {{-- Text & Badges --}}
                        <div style="min-width: 0; flex: 1 1 0%; display: flex; flex-direction: column; gap: 0.125rem;">
                            <div style="display: flex; align-items: center; gap: 0.375rem; min-width: 0;">
                                <span class="templat-title" title="{{ $item->judul }}">
                                    {{ $item->judul }}
                                </span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.375rem; min-width: 0;">
                                <span class="templat-filename" title="{{ $item->file_name }}">
                                    {{ $item->file_name }}
                                </span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.25rem; margin-top: 0.25rem; flex-wrap: wrap;">
                                <x-filament::badge color="gray" size="xs">
                                    {{ $item->kategori }}
                                </x-filament::badge>
                                @if($item->bahasa === 'ID')
                                    <x-filament::badge color="info" size="xs">
                                        Indonesia
                                    </x-filament::badge>
                                @else
                                    <x-filament::badge color="warning" size="xs">
                                        English
                                    </x-filament::badge>
                                @endif
                                <x-filament::badge color="primary" size="xs">
                                    {{ strtoupper(pathinfo($item->file_name, PATHINFO_EXTENSION) ?: 'DOCX') }}
                                </x-filament::badge>

                                @if($item->file_size_formatted)
                                    <span style="font-size: 0.7rem; color: #9ca3af; margin-left: 0.25rem;">
                                        {{ $item->file_size_formatted }}
                                    </span>
                                @endif

                                @if($isAdmin && ! $item->is_active)
                                    <x-filament::badge color="danger" size="xs">
                                        Diarsipkan (Nonaktif)
                                    </x-filament::badge>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Right Actions: Admin Management + Download Button --}}
                    <div style="display: flex; align-items: center; gap: 0.375rem; flex-shrink: 0; margin-left: 0.5rem;">
                        @if($isAdmin)
                            {{-- Edit / Ganti Berkas Action --}}
                            <x-filament::icon-button
                                icon="heroicon-m-pencil-square"
                                color="gray"
                                size="sm"
                                tooltip="Ganti Berkas / Edit Informasi"
                                wire:click="mountAction('editTemplate', { id: {{ $item->id }} })"
                            />

                            {{-- Toggle Aktif / Nonaktif Action --}}
                            <x-filament::icon-button
                                icon="{{ $item->is_active ? 'heroicon-m-eye' : 'heroicon-m-eye-slash' }}"
                                color="{{ $item->is_active ? 'success' : 'warning' }}"
                                size="sm"
                                tooltip="{{ $item->is_active ? 'Klik untuk Nonaktifkan (Arsipkan templat ini)' : 'Klik untuk Aktifkan kembali' }}"
                                wire:click="toggleActive({{ $item->id }})"
                            />

                            {{-- Delete Action --}}
                            <x-filament::icon-button
                                icon="heroicon-m-trash"
                                color="danger"
                                size="sm"
                                tooltip="Hapus Templat"
                                wire:click="mountAction('deleteTemplate', { id: {{ $item->id }} })"
                            />
                        @endif

                        {{-- Download Button --}}
                        <x-filament::button
                            tag="a"
                            href="{{ $item->url }}"
                            target="_blank"
                            download="{{ $item->file_name }}"
                            color="primary"
                            size="sm"
                            icon="heroicon-m-arrow-down-tray"
                        >
                            Unduh
                        </x-filament::button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Empty State --}}
        <div
            x-show="matchCount === 0"
            style="text-align: center; padding: 3rem 1rem; color: #6b7280;"
            x-transition
        >
            <x-filament::icon icon="heroicon-o-document-magnifying-glass" style="height: 3rem; width: 3rem; margin: 0 auto 0.75rem auto; color: #9ca3af;" />
            <p style="font-weight: 500; font-size: 0.875rem;">Tidak ada templat yang cocok dengan kata kunci pencarian.</p>
        </div>
    </div>
</x-filament-panels::page>
