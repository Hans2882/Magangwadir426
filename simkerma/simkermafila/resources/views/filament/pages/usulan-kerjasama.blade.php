<x-filament-panels::page>
    <div class="flex flex-col rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
        
        <!-- Header -->
        <div class="flex flex-col gap-4 p-4 sm:px-6 sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 dark:border-white/10">
            <div>
                <h2 class="text-lg font-bold tracking-tight text-gray-950 dark:text-white">Usulan Kerjasama</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Daftar inisiasi kerjasama baru yang diajukan oleh Program Studi.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <x-filament::button icon="heroicon-o-plus" color="primary">
                    Ajukan Usulan Baru
                </x-filament::button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto w-full">
            <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/5 whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-6 py-3 text-sm font-semibold text-gray-950 dark:text-white">Nama Mitra</th>
                        <th class="px-6 py-3 text-sm font-semibold text-gray-950 dark:text-white">Bentuk Kerjasama</th>
                        <th class="px-6 py-3 text-sm font-semibold text-gray-950 dark:text-white">Pengusul</th>
                        <th class="px-6 py-3 text-sm font-semibold text-gray-950 dark:text-white">Status</th>
                        <th class="px-6 py-3 text-sm font-semibold text-gray-950 dark:text-white text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    
                    <!-- Row 1 -->
                    <tr class="bg-white hover:bg-gray-50 dark:bg-gray-900 dark:hover:bg-white/5 transition duration-75">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                                    <x-filament::icon icon="heroicon-o-globe-alt" class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                </div>
                                <div>
                                    <div class="font-medium text-gray-950 dark:text-white">Atase Pendidikan dan Kebudayaan Manila</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Philippines</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-2">
                                <x-filament::badge color="info">Pertukaran Informasi</x-filament::badge>
                                <x-filament::badge color="success">Student Exchange</x-filament::badge>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-950 dark:text-white">Drs. Zubaidi, M.Pd</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">D3 Administrasi Bisnis</div>
                        </td>
                        <td class="px-6 py-4">
                            <x-filament::badge color="warning" icon="heroicon-m-clock">Menunggu Review</x-filament::badge>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <x-filament::icon-button icon="heroicon-m-eye" color="gray" tooltip="Lihat Detail" />
                        </td>
                    </tr>

                    <!-- Row 2 -->
                    <tr class="bg-white hover:bg-gray-50 dark:bg-gray-900 dark:hover:bg-white/5 transition duration-75">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                                    <x-filament::icon icon="heroicon-o-building-office-2" class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                </div>
                                <div>
                                    <div class="font-medium text-gray-950 dark:text-white">PT. Telekomunikasi Indonesia</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Indonesia</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-2">
                                <x-filament::badge color="primary">Magang Industri</x-filament::badge>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-950 dark:text-white">Dr. Eng. Rosa Andrie</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">D4 Teknik Informatika</div>
                        </td>
                        <td class="px-6 py-4">
                            <x-filament::badge color="success" icon="heroicon-m-check-circle">Disetujui</x-filament::badge>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <x-filament::icon-button icon="heroicon-m-eye" color="gray" tooltip="Lihat Detail" />
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
        
        <!-- Footer / Pagination Mockup -->
        <div class="border-t border-gray-200 p-4 dark:border-white/10 text-sm text-gray-500 dark:text-gray-400 flex justify-between items-center">
            <div>Showing 1 to 2 of 2 results</div>
            <div class="flex gap-2">
                <x-filament::button color="gray" size="sm" disabled>Previous</x-filament::button>
                <x-filament::button color="gray" size="sm" disabled>Next</x-filament::button>
            </div>
        </div>

    </div>
</x-filament-panels::page>
