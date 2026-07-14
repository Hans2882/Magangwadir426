<x-filament-panels::page>
    <x-filament::section>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @php
                $templates = [
                    'Templat NK MoU Polinema Short_Indo.docx',
                    'Templat NK MoU Polinema Standart_Indo.docx',
                    'Template MoU Polinema Short_Eng.docx',
                    'Template MoU Polinema Standard_Eng.docx',
                    'Template_IA.docx',
                    'Template_Laporan Pelaksanaan Kerja Sama.docx',
                    'Template_Laporan Pelaksanaan Kerja Sama_ENG.docx',
                    'Template_PKS.docx',
                ];
            @endphp
            
            @foreach($templates as $template)
                <div class="p-4 rounded-lg border border-gray-200 dark:border-white/10 flex items-center justify-between shadow-sm bg-white dark:bg-gray-900">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <x-filament::icon
                            icon="heroicon-o-document-text"
                            class="h-8 w-8 text-primary-500 shrink-0"
                        />
                        <span class="font-medium text-sm text-gray-700 dark:text-gray-200 truncate" title="{{ $template }}">
                            {{ $template }}
                        </span>
                    </div>
                    
                    <x-filament::button
                        tag="a"
                        href="{{ asset('templates/' . $template) }}"
                        target="_blank"
                        download="{{ $template }}"
                        color="primary"
                        size="sm"
                        icon="heroicon-m-arrow-down-tray"
                        class="shrink-0 ml-2"
                    >
                        Unduh
                    </x-filament::button>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
