<div class="space-y-4">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 class="font-semibold text-blue-900 mb-2">Base URL</h4>
        <code class="text-sm bg-white px-2 py-1 rounded border border-blue-100">{{ url('/api/v1') }}</code>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 class="font-semibold text-blue-900 mb-2">Authentication</h4>
        <p class="text-sm text-blue-800 mb-2">Add your API key to the request header:</p>
        <code class="text-xs bg-white px-2 py-1 rounded border border-blue-100 block">X-API-Key: YOUR_API_KEY</code>
        <p class="text-xs text-blue-700 mt-2">Or as query parameter:</p>
        <code class="text-xs bg-white px-2 py-1 rounded border border-blue-100 block">?api_key=YOUR_API_KEY</code>
    </div>

    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <h4 class="font-semibold text-green-900 mb-3">Available Endpoints</h4>
        
        <div class="space-y-3">
            <!-- GET Mitra -->
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="bg-green-600 text-white px-2 py-1 text-xs rounded font-bold">GET</span>
                    <code class="text-sm font-mono">/mitra</code>
                </div>
                <p class="text-sm text-green-800">Get all mitra data with pagination</p>
                <p class="text-xs text-green-700 mt-1">Query params: kategori_id, negara_id, tipe (dalam_negeri/luar_negeri), search, per_page</p>
            </div>

            <!-- GET Single Mitra -->
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="bg-green-600 text-white px-2 py-1 text-xs rounded font-bold">GET</span>
                    <code class="text-sm font-mono">/mitra/{id}</code>
                </div>
                <p class="text-sm text-green-800">Get single mitra data with relationships</p>
            </div>

            <!-- GET Mitra Kerjasama -->
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="bg-green-600 text-white px-2 py-1 text-xs rounded font-bold">GET</span>
                    <code class="text-sm font-mono">/mitra/{id}/kerjasama</code>
                </div>
                <p class="text-sm text-green-800">Get all kerjasama for a specific mitra</p>
            </div>

            <!-- GET API Key Info -->
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="bg-green-600 text-white px-2 py-1 text-xs rounded font-bold">GET</span>
                    <code class="text-sm font-mono">/api-key/info</code>
                </div>
                <p class="text-sm text-green-800">Get information about your API key</p>
            </div>
        </div>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
        <h4 class="font-semibold text-amber-900 mb-2">Response Format</h4>
        <pre class="text-xs bg-white px-2 py-1 rounded border border-amber-100 overflow-auto"><code>{
  "success": true,
  "message": "Data retrieved successfully",
  "data": [...],
  "pagination": {
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7,
    "from": 1,
    "to": 15
  }
}</code></pre>
    </div>

    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <h4 class="font-semibold text-red-900 mb-2">Example Request (cURL)</h4>
        <pre class="text-xs bg-white px-2 py-1 rounded border border-red-100 overflow-auto"><code>curl -X GET "{{ url('/api/v1/mitra?per_page=10') }}" \
  -H "X-API-Key: YOUR_API_KEY"</code></pre>
    </div>
</div>
