<div
    x-data="{
        endpoint: @js(url('/api/mitra')),
        apiKey: @js(config('services.web_api.key')),
        showApiKey: false,

        filters: @js($filters ?? []),
        activeTab: @js($activeTab ?? 'dalam_negeri'),

        buildUrl(includeApiKey = false) {
            const params = new URLSearchParams();

            params.set(
                'tab',
                this.activeTab === 'luar_negeri'
                    ? 'luar_negeri'
                    : 'dalam_negeri'
            );

            const simpleFilters = {
                kategori_id: this.filters?.kategori_id?.value,
                negara_id: this.filters?.negara_id?.value,
                status_kerjasama: this.filters?.status_kerjasama?.value,
            };

            Object.entries(simpleFilters).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    params.set(key, value);
                }
            });

            const jenisDokumen =
                this.filters?.jenis_dokumen?.values ?? [];

            jenisDokumen.forEach(value => {
                if (value !== null && value !== '') {
                    params.append('jenis_dokumen[]', value);
                }
            });

            if (includeApiKey && this.apiKey) {
                params.set('api_key', this.apiKey);
            }

            return this.endpoint + '?' + params.toString();
        },

        copyText(text, message) {
            navigator.clipboard
                .writeText(text)
                .then(() => alert(message))
                .catch(() => alert('Gagal menyalin.'));
        },

        getJson() {
            window.open(this.buildUrl(true), '_blank');
        },

        get filteredUrl() {
            return this.buildUrl(false);
        },

        get statusLabel() {
            return {
                none: 'Belum Ada Kerjasama',
                active: 'Aktif',
                expiring: 'Akan Berakhir',
                expired: 'Berakhir',
            }[this.filters?.status_kerjasama?.value] ?? 'Semua';
        },

        get documentLabel() {
            const labels = {
                1: 'MoU',
                2: 'MoA',
                3: 'PKS',
                4: 'IA',
                5: 'SPK',
                6: 'LoC',
                7: 'LoI',
            };

            const values =
                this.filters?.jenis_dokumen?.values ?? [];

            return values.length
                ? values.map(value => labels[value] ?? value).join(', ')
                : 'Semua';
        }
    }"
    style="
        font-size: 12px;
        max-width: 100%;
    "
>

    {{-- HEADER --}}
    <div style="margin-bottom: 12px;">
        <h1 style="
            margin: 0 0 3px 0;
            font-size: 20px;
            font-weight: 600;
            color: #111827;
        ">
            WebAPI Mitra
        </h1>

        <div style="
            color: #6b7280;
            font-size: 12px;
        ">
            Gunakan WebAPI berikut untuk mendapatkan data mitra.
        </div>
    </div>


    {{-- TAB --}}
    <div style="
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
    ">
        <strong style="color: #374151;">
            Data:
        </strong>

        <span
            x-text="
                activeTab === 'luar_negeri'
                    ? 'Luar Negeri'
                    : 'Dalam Negeri'
            "
            style="
                padding: 4px 9px;
                background: #eff6ff;
                color: #1d4ed8;
                border-radius: 5px;
                font-size: 11px;
                font-weight: 500;
            "
        ></span>
    </div>


    {{-- ENDPOINT --}}
    <div style="margin-bottom: 12px;">

        <label style="
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
        ">
            Endpoint WebAPI
        </label>

        <div style="display: flex; gap: 6px;">

            <input
                type="text"
                :value="filteredUrl"
                readonly
                style="
                    flex: 1;
                    min-width: 0;
                    height: 34px;
                    padding: 0 9px;
                    border: 1px solid #d1d5db;
                    border-radius: 5px;
                    background: #f9fafb;
                    color: #4b5563;
                    font-size: 11px;
                "
            >

            <button
                type="button"
                @click="copyText(filteredUrl, 'Tautan berhasil disalin.')"
                style="
                    height: 34px;
                    padding: 0 10px;
                    border: none;
                    background: #2563eb;
                    color: white;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 11px;
                    white-space: nowrap;
                "
            >
                Salin
            </button>

        </div>
    </div>


    {{-- FILTER + API KEY --}}
    <div style="
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 12px;
    ">

        {{-- FILTER --}}
        <div>

            <label style="
                display: block;
                margin-bottom: 5px;
                font-size: 12px;
                font-weight: 600;
                color: #374151;
            ">
                Filter Aktif
            </label>

            <div style="
                padding: 9px 11px;
                background: #f9fafb;
                border: 1px solid #e5e7eb;
                border-radius: 5px;
                color: #4b5563;
                font-size: 11px;
                line-height: 1.6;
            ">

                <div>
                    <strong>Kategori:</strong>
                    <span x-text="filters?.kategori_id?.value || 'Semua'"></span>
                </div>

                <div>
                    <strong>Negara:</strong>
                    <span x-text="filters?.negara_id?.value || 'Semua'"></span>
                </div>

                <div>
                    <strong>Status:</strong>
                    <span x-text="statusLabel"></span>
                </div>

                <div>
                    <strong>Dokumen:</strong>
                    <span x-text="documentLabel"></span>
                </div>

            </div>

        </div>


        {{-- API KEY --}}
        <div>

            <label style="
                display: block;
                margin-bottom: 5px;
                font-size: 12px;
                font-weight: 600;
                color: #374151;
            ">
                Web API Key
            </label>

            <div style="display: flex; gap: 6px;">

                <input
                    :type="showApiKey ? 'text' : 'password'"
                    x-model="apiKey"
                    readonly
                    style="
                        flex: 1;
                        min-width: 0;
                        height: 34px;
                        padding: 0 9px;
                        border: 1px solid #d1d5db;
                        border-radius: 5px;
                        background: #f9fafb;
                        color: #4b5563;
                        font-size: 11px;
                    "
                >

                <button
                    type="button"
                    @click="showApiKey = !showApiKey"
                    x-text="showApiKey ? 'Hide' : 'Show'"
                    style="
                        height: 34px;
                        padding: 0 9px;
                        border: 1px solid #d1d5db;
                        background: #f3f4f6;
                        color: #374151;
                        border-radius: 5px;
                        cursor: pointer;
                        font-size: 10px;
                    "
                ></button>

                <button
                    type="button"
                    @click="copyText(apiKey, 'API Key berhasil disalin.')"
                    style="
                        height: 34px;
                        padding: 0 9px;
                        border: none;
                        background: #2563eb;
                        color: white;
                        border-radius: 5px;
                        cursor: pointer;
                        font-size: 10px;
                        white-space: nowrap;
                    "
                >
                    Salin
                </button>

            </div>

        </div>

    </div>


    {{-- BUTTON JSON --}}
    <div style="margin-bottom: 12px;">

        <button
            type="button"
            @click="getJson()"
            style="
                height: 34px;
                padding: 0 13px;
                background: #2563eb;
                color: white;
                border: none;
                border-radius: 5px;
                font-size: 11px;
                font-weight: 500;
                cursor: pointer;
            "
        >
            Dapatkan JSON
        </button>

    </div>


    {{-- INFO --}}
    <div style="
        padding: 8px 11px;
        background: #eff6ff;
        border-radius: 5px;
        color: #1e40af;
        font-size: 11px;
        line-height: 1.4;
    ">
        Gunakan API Key pada parameter
        <strong>api_key</strong>
        ketika mengakses endpoint.
    </div>

</div>