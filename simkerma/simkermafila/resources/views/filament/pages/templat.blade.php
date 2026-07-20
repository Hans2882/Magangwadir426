<x-filament-panels::page>
    <x-filament::section>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1rem;">
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
                <div style="padding: 1rem; border-radius: 0.75rem; border: 1px solid rgba(156, 163, 175, 0.2); display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); background-color: rgb(255, 255, 255); dark:background-color: rgba(17, 24, 39, 1);">
                    <div style="display: flex; align-items: center; gap: 0.75rem; overflow: hidden;">
                        <x-filament::icon
                            icon="heroicon-o-document-text"
                            style="height: 2rem; width: 2rem; flex-shrink: 0;"
                            class="text-primary-500"
                        />
                        <span style="font-weight: 500; font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: rgb(55, 65, 81);" title="{{ $template }}">
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
                        style="flex-shrink: 0; margin-left: 0.5rem;"
                    >
                        Unduh
                    </x-filament::button>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
