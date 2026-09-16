<div
    x-data="{
        endpoint: @js($endpoint),
        apiKey: @js(config('services.web_api.key')),
        label: @js($label),
        filters: @js($filters ?? []),
        showApiKey: false,

        buildUrl(includeApiKey = false) {
            const params = new URLSearchParams();

            Object.entries(this.filters ?? {}).forEach(([key, value]) => {

                /*
                |--------------------------------------------------------------------------
                | ARRAY FILTER
                |--------------------------------------------------------------------------
                */

                if (Array.isArray(value)) {

                    value.forEach(item => {

                        if (
                            item !== null &&
                            item !== undefined &&
                            item !== ''
                        ) {
                            params.append(
                                `${key}[]`,
                                item
                            );
                        }

                    });

                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | SINGLE FILTER
                |--------------------------------------------------------------------------
                */

                if (
                    value !== null &&
                    value !== undefined &&
                    value !== ''
                ) {
                    params.set(
                        key,
                        value
                    );
                }
            });

            /*
            |--------------------------------------------------------------------------
            | API KEY
            |--------------------------------------------------------------------------
            */

            if (
                includeApiKey &&
                this.apiKey
            ) {
                params.set(
                    'api_key',
                    this.apiKey
                );
            }

            const query = params.toString();

            return query
                ? `${this.endpoint}?${query}`
                : this.endpoint;
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
                this.buildUrl(true),
                '_blank'
            );
        },

        /*
        |--------------------------------------------------------------------------
        | FILTERED URL
        |--------------------------------------------------------------------------
        */

        get filteredUrl() {

            return this.buildUrl(false);
        }
    }"
    style="
        font-size: 12px;
        max-width: 100%;
    "
>

    {{-- HEADER --}}

    <div style="margin-bottom: 12px;">

        <h1
            style="
                margin: 0 0 3px;
                font-size: 20px;
                font-weight: 600;
                color: #111827;
            "
        >
            WebAPI
            <span x-text="label"></span>
        </h1>

        <div
            style="
                color: #6b7280;
                font-size: 12px;
            "
        >
            Gunakan WebAPI berikut untuk mendapatkan data
            <span x-text="label"></span>.
        </div>

    </div>


    {{-- ENDPOINT --}}

    <div style="margin-bottom: 12px;">

        <label
            style="
                display: block;
                margin-bottom: 5px;
                font-size: 12px;
                font-weight: 600;
                color: #374151;
            "
        >
            Endpoint WebAPI
        </label>

        <div
            style="
                display: flex;
                gap: 6px;
            "
        >

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
                @click="
                    copyText(
                        filteredUrl,
                        'Tautan berhasil disalin.'
                    )
                "
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

    <div
        style="
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        "
    >

        {{-- FILTER AKTIF --}}

        <div>

            <label
                style="
                    display: block;
                    margin-bottom: 5px;
                    font-size: 12px;
                    font-weight: 600;
                    color: #374151;
                "
            >
                Filter Aktif
            </label>

            <div
                style="
                    padding: 9px 11px;
                    background: #f9fafb;
                    border: 1px solid #e5e7eb;
                    border-radius: 5px;
                    color: #4b5563;
                    font-size: 11px;
                    line-height: 1.6;
                "
            >

                <template
                    x-for="(value, key) in filters"
                    :key="key"
                >

                    <div>

                        <strong
                            x-text="
                                key.replaceAll('_', ' ') + ':'
                            "
                        ></strong>

                        <span
                            x-text="
                                Array.isArray(value)
                                    ? value.join(', ')
                                    : value
                            "
                        ></span>

                    </div>

                </template>

                <div
                    x-show="
                        Object.keys(filters ?? {}).length === 0
                    "
                >
                    Semua data
                </div>

            </div>

        </div>


        {{-- API KEY --}}

        <div>

            <label
                style="
                    display: block;
                    margin-bottom: 5px;
                    font-size: 12px;
                    font-weight: 600;
                    color: #374151;
                "
            >
                Web API Key
            </label>

            <div
                style="
                    display: flex;
                    gap: 6px;
                "
            >

                <input
                    :type="
                        showApiKey
                            ? 'text'
                            : 'password'
                    "
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
                    @click="
                        showApiKey = !showApiKey
                    "
                    x-text="
                        showApiKey
                            ? 'Hide'
                            : 'Show'
                    "
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
                    @click="
                        copyText(
                            apiKey,
                            'API Key berhasil disalin.'
                        )
                    "
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


    {{-- GET JSON --}}

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


    {{-- INFORMATION --}}

    <div
        style="
            padding: 8px 11px;
            background: #eff6ff;
            border-radius: 5px;
            color: #1e40af;
            font-size: 11px;
            line-height: 1.4;
        "
    >
        Gunakan API Key pada parameter
        <strong>api_key</strong>
        ketika mengakses endpoint.
    </div>

</div>