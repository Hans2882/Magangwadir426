<div
    x-data="{
        filters: @js($filters ?? []),
        activeTab: @js($activeTab ?? 'dalam_negeri'),

    endpoint: @js(url('/api/mitra')),
    apiKey: @js(config('services.web_api.key')),
    showApiKey: false,

    buildParams(includeApiKey = false) {

        const params = new URLSearchParams();

        /*
        |--------------------------------------------------------------------------
        | TAB
        |--------------------------------------------------------------------------
        */

        params.append(
            'tab',
            this.activeTab === 'luar_negeri'
                ? 'luar_negeri'
                : 'dalam_negeri'
        );


        /*
        |--------------------------------------------------------------------------
        | KATEGORI IKU
        |--------------------------------------------------------------------------
        */

        const kategoriId =
            this.filters?.kategori_id?.value ?? null;

        if (
            kategoriId !== null &&
            kategoriId !== ''
        ) {
            params.append(
                'kategori_id',
                kategoriId
            );
        }


        /*
        |--------------------------------------------------------------------------
        | NEGARA
        |--------------------------------------------------------------------------
        */

        const negaraId =
            this.filters?.negara_id?.value ?? null;

        if (
            negaraId !== null &&
            negaraId !== ''
        ) {
            params.append(
                'negara_id',
                negaraId
            );
        }


        /*
        |--------------------------------------------------------------------------
        | STATUS KERJASAMA
        |--------------------------------------------------------------------------
        */

        const statusKerjasama =
            this.filters?.status_kerjasama?.value ?? null;

        if (
            statusKerjasama !== null &&
            statusKerjasama !== ''
        ) {
            params.append(
                'status_kerjasama',
                statusKerjasama
            );
        }


        /*
        |--------------------------------------------------------------------------
        | JENIS DOKUMEN
        |--------------------------------------------------------------------------
        */

        const jenisDokumen =
            this.filters?.jenis_dokumen?.values ?? [];

        if (Array.isArray(jenisDokumen)) {

            jenisDokumen.forEach(item => {

                if (
                    item !== null &&
                    item !== ''
                ) {
                    params.append(
                        'jenis_dokumen[]',
                        item
                    );
                }

            });

        }


        /*
        |--------------------------------------------------------------------------
        | API KEY
        |--------------------------------------------------------------------------
        */

        if (
            includeApiKey &&
            this.apiKey
        ) {
            params.append(
                'api_key',
                this.apiKey
            );
        }


        return params;
    },


    /*
    |--------------------------------------------------------------------------
    | FILTERED API URL
    |--------------------------------------------------------------------------
    */

    getFilteredUrl() {

        const params =
            this.buildParams(false);

        const query =
            params.toString();

        return this.endpoint +
            (query ? '?' + query : '');
    },


    /*
    |--------------------------------------------------------------------------
    | JSON URL
    |--------------------------------------------------------------------------
    */

    getJsonUrl() {

        const params =
            this.buildParams(true);

        return this.endpoint +
            '?' +
            params.toString();
    },


    /*
    |--------------------------------------------------------------------------
    | COPY
    |--------------------------------------------------------------------------
    */

    copyText(text, message) {

        navigator.clipboard
            .writeText(text)
            .then(() => {

                alert(message);

            })
            .catch(() => {

                alert('Gagal menyalin.');

            });
    },


    /*
    |--------------------------------------------------------------------------
    | OPEN JSON
    |--------------------------------------------------------------------------
    */

    getJson() {

        window.open(
            this.getJsonUrl(),
            '_blank'
        );

    }
}"
```

>

```
{{-- Judul --}}
<div style="margin-bottom: 24px;">

    <h1 style="
        margin: 0 0 8px 0;
        font-size: 24px;
        font-weight: 600;
        color: #111827;
    ">
        WebAPI Mitra
    </h1>

    <div style="
        color: #6b7280;
        font-size: 14px;
    ">
        Gunakan WebAPI berikut untuk mendapatkan data mitra.
    </div>

</div>


{{-- Tab Aktif --}}
<div style="margin-bottom: 22px;">

    <label style="
        display: block;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 8px;
        color: #374151;
    ">
        Data Mitra
    </label>

    <div style="
        display: inline-block;
        padding: 7px 12px;
        background: #eff6ff;
        color: #1d4ed8;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 500;
    "
    >
        <span
            x-text="
                activeTab === 'luar_negeri'
                    ? 'Luar Negeri'
                    : 'Dalam Negeri'
            "
        ></span>
    </div>

</div>


{{-- Endpoint --}}
<div style="margin-bottom: 22px;">

    <label style="
        display: block;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 8px;
        color: #374151;
    ">
        Endpoint WebAPI
    </label>

    <div style="
        display: flex;
        gap: 8px;
        width: 100%;
    ">

        <input
            type="text"
            :value="getFilteredUrl()"
            readonly
            style="
                flex: 1;
                min-width: 0;
                height: 40px;
                padding: 0 11px;
                border: 1px solid #d1d5db;
                border-radius: 7px;
                background: #f9fafb;
                color: #4b5563;
                font-size: 13px;
                outline: none;
            "
        >

        <button
            type="button"
            @click="copyText(
                getFilteredUrl(),
                'Tautan berhasil disalin.'
            )"
            style="
                height: 40px;
                padding: 0 14px;
                border: none;
                background: #2563eb;
                color: white;
                border-radius: 7px;
                cursor: pointer;
                font-size: 12px;
                white-space: nowrap;
            "
        >
            Salin Tautan
        </button>

    </div>

</div>


{{-- Filter Aktif --}}
<div style="margin-bottom: 22px;">

    <label style="
        display: block;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 8px;
        color: #374151;
    ">
        Filter Aktif
    </label>

    <div style="
        padding: 13px 15px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 7px;
        color: #4b5563;
        font-size: 13px;
        line-height: 1.8;
    ">

        <div>
            <strong>Kategori IKU:</strong>

            <span
                x-text="
                    filters?.kategori_id?.value
                        ? filters.kategori_id.value
                        : 'Semua'
                "
            ></span>
        </div>

        <div>
            <strong>Negara:</strong>

            <span
                x-text="
                    filters?.negara_id?.value
                        ? filters.negara_id.value
                        : 'Semua'
                "
            ></span>
        </div>

        <div>
            <strong>Status Kerjasama:</strong>

            <span
                x-text="
                    {
                        none: 'Belum Ada Kerjasama',
                        active: 'Aktif',
                        expiring: 'Akan Berakhir',
                        expired: 'Berakhir'
                    }[
                        filters?.status_kerjasama?.value
                    ] ?? 'Semua'
                "
            ></span>
        </div>

        <div>
            <strong>Jenis Dokumen:</strong>

            <span
                x-text="
                    (() => {
                        const values =
                            filters?.jenis_dokumen?.values ?? [];

                        const labels = {
                            1: 'MoU',
                            2: 'MoA',
                            3: 'PKS',
                            4: 'IA',
                            5: 'SPK',
                            6: 'LoC',
                            7: 'LoI'
                        };

                        if (!values.length) {
                            return 'Semua';
                        }

                        return values
                            .map(value => labels[value] ?? value)
                            .join(', ');
                    })()
                "
            ></span>
        </div>

    </div>

</div>


{{-- API Key --}}
<div style="margin-bottom: 22px;">

    <label style="
        display: block;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 8px;
        color: #374151;
    ">
        Web API Key
    </label>

    <div style="
        display: flex;
        gap: 8px;
        width: 100%;
    ">

        <input
            :type="showApiKey ? 'text' : 'password'"
            x-model="apiKey"
            readonly
            style="
                flex: 1;
                min-width: 0;
                height: 40px;
                padding: 0 11px;
                border: 1px solid #d1d5db;
                border-radius: 7px;
                background: #f9fafb;
                color: #4b5563;
                font-size: 13px;
                outline: none;
            "
        >

        <button
            type="button"
            @click="showApiKey = !showApiKey"
            style="
                height: 40px;
                padding: 0 14px;
                border: 1px solid #d1d5db;
                background: #f3f4f6;
                color: #374151;
                border-radius: 7px;
                cursor: pointer;
                font-size: 12px;
                white-space: nowrap;
            "
            x-text="
                showApiKey
                    ? 'Sembunyikan'
                    : 'Tampilkan'
            "
        ></button>

        <button
            type="button"
            @click="copyText(
                apiKey,
                'API Key berhasil disalin.'
            )"
            style="
                height: 40px;
                padding: 0 14px;
                border: none;
                background: #2563eb;
                color: white;
                border-radius: 7px;
                cursor: pointer;
                font-size: 12px;
                white-space: nowrap;
            "
        >
            Salin API Key
        </button>

    </div>

</div>


{{-- Dapatkan JSON --}}
<div style="
    margin-top: 5px;
    margin-bottom: 20px;
">

    <button
        type="button"
        @click="getJson()"
        style="
            display: inline-block;
            padding: 10px 16px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
        "
    >
        Dapatkan JSON
    </button>

</div>


{{-- Informasi --}}
<div style="
    padding: 13px 15px;
    background: #eff6ff;
    border-radius: 7px;
    color: #1e40af;
    font-size: 13px;
    line-height: 1.5;
">
    Gunakan API Key pada parameter
    <strong>api_key</strong>
    ketika mengakses endpoint.
</div>

</div>
