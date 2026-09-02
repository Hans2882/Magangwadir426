<div class="p-6 space-y-6">
    <div class="space-y-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.658 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
                Endpoint WebAPI
            </h3>

            <div class="relative">
                <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 md:p-5 space-y-3">
                        <div class="bg-white rounded-lg border border-slate-200 p-4 font-mono text-sm text-slate-700 break-all overflow-x-auto shadow-inner">
                            <span class="text-blue-600">{{ $apiUrl }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 pt-2" x-data="{ copied: false }">
            <button
                type="button"
                class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 transition-all duration-150 shadow-md hover:shadow-lg disabled:opacity-50"
                @click="navigator.clipboard.writeText('{{ $apiUrl }}'); copied = true; setTimeout(() => copied = false, 2500)"
            >
                <svg
                    class="w-4 h-4 mr-2"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                    />
                </svg>
                <span>Salin Tautan</span>
            </button>

            <span
                class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg"
                x-show="copied"
                x-transition
            >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Tautan berhasil disalin
            </span>
        </div>
    </div>
</div>
