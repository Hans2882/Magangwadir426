<div style="
    font-family: Arial, sans-serif;
    color: #1f2937;
    width: 100%;
">

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
                id="endpoint"
                type="text"
                value="{{ url('/api/mitra') }}"
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
                onclick="copyEndpoint()"
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
                id="apiKey"
                type="text"
                value="{{ config('services.web_api.key') }}"
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
                onclick="copyApiKey()"
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


    {{-- Tombol --}}
    <div style="
        margin-top: 5px;
        margin-bottom: 20px;
    ">

        <a
            href="{{ url('/api/mitra') }}"
            onclick="return getJson(event)"
            style="
                display: inline-block;
                padding: 10px 16px;
                background: #2563eb;
                color: white;
                border-radius: 7px;
                text-decoration: none;
                font-size: 13px;
                font-weight: 500;
            "
        >
            Dapatkan JSON
        </a>

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
        Gunakan API Key pada header
        <strong>X-API-KEY</strong>
        ketika mengakses endpoint.
    </div>

</div>


<script>

function copyEndpoint() {
    const input = document.getElementById('endpoint');

    navigator.clipboard.writeText(input.value);

    alert('Tautan berhasil disalin.');
}


function copyApiKey() {
    const input = document.getElementById('apiKey');

    navigator.clipboard.writeText(input.value);

    alert('API Key berhasil disalin.');
}


function getJson(event) {
    event.preventDefault();

    const endpoint = document.getElementById('endpoint').value;
    const apiKey = document.getElementById('apiKey').value;

    const url =
        endpoint +
        '?api_key=' +
        encodeURIComponent(apiKey);

    window.open(url, '_blank');

    return false;
}

</script>