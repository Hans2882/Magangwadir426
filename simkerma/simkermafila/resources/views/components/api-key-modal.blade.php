<div class="p-6 space-y-6">
    <div>
        <h3 class="text-sm font-medium text-gray-900 mb-3">
            Endpoint WebAPI
        </h3>

        <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 mb-3">
            <code class="text-xs text-gray-700 break-all block font-mono">
                {{ $apiUrl }}
            </code>
        </div>

        <div class="flex items-center gap-2" x-data="{ copied: false }">
            <button
                type="button"
                class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors"
                @click="navigator.clipboard.writeText('{{ $apiUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
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
                class="text-green-600 text-sm font-medium"
                x-show="copied"
                x-transition
            >
                Tautan berhasil disalin
            </span>
        </div>
    </div>
</div>
