<x-filament-panels::page>

{{-- Header Database --}}
<x-filament::section>
    <div style="display: flex; align-items: center; gap: 1rem;">

        <div
            style="
                padding: 0.75rem;
                border-radius: 0.75rem;
                background-color: rgba(59, 130, 246, 0.1);
                flex-shrink: 0;
            "
        >
            <x-filament::icon
                icon="heroicon-o-circle-stack"
                style="height: 1.75rem; width: 1.75rem;"
                class="text-primary-500"
            />
        </div>

        <div style="min-width: 0;">
            <h2
                style="
                    margin: 0;
                    font-size: 1.125rem;
                    font-weight: 700;
                    color: rgb(17, 24, 39);
                "
            >
                {{ $this->getDatabase() }}
            </h2>

            <p
                style="
                    margin: 0.2rem 0 0;
                    font-size: 0.875rem;
                    color: rgb(107, 114, 128);
                "
            >
                Database Explorer
            </p>
        </div>

        <div style="margin-left: auto; flex-shrink: 0;">
            <x-filament::badge color="success">
                {{ count($this->getTables()) }} tabel
            </x-filament::badge>
        </div>

    </div>
</x-filament::section>


{{-- Navigasi Tabel --}}
@foreach ($this->getAvailableTableGroups() as $group => $tables)

    <x-filament::section :heading="$group">

        <div
            style="
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 0.75rem;
            "
        >

            @foreach ($tables as $table)

                <a
                    href="#structure-{{ md5($table) }}"
                    style="
                        display: flex;
                        align-items: center;
                        gap: 0.75rem;
                        min-width: 0;
                        padding: 0.875rem;
                        border-radius: 0.75rem;
                        border: 1px solid rgba(156, 163, 175, 0.25);
                        background-color: rgb(255, 255, 255);
                        text-decoration: none;
                        transition: all 0.2s ease;
                    "
                    onmouseover="this.style.borderColor='rgb(59, 130, 246)'; this.style.backgroundColor='rgba(59, 130, 246, 0.04)'"
                    onmouseout="this.style.borderColor='rgba(156, 163, 175, 0.25)'; this.style.backgroundColor='rgb(255, 255, 255)'"
                >

                    <div
                        style="
                            padding: 0.6rem;
                            border-radius: 0.6rem;
                            background-color: rgba(59, 130, 246, 0.1);
                            flex-shrink: 0;
                        "
                    >
                        <x-filament::icon
                            icon="heroicon-o-table-cells"
                            style="height: 1.25rem; width: 1.25rem;"
                            class="text-primary-500"
                        />
                    </div>

                    <div style="min-width: 0; flex: 1;">

                        <div
                            style="
                                font-size: 0.875rem;
                                font-weight: 600;
                                color: rgb(31, 41, 55);
                                white-space: nowrap;
                                overflow: hidden;
                                text-overflow: ellipsis;
                            "
                            title="{{ $this->getTableLabel($table) }}"
                        >
                            {{ $this->getTableLabel($table) }}
                        </div>

                        <div
                            style="
                                margin-top: 0.15rem;
                                font-size: 0.75rem;
                                color: rgb(107, 114, 128);
                                white-space: nowrap;
                                overflow: hidden;
                                text-overflow: ellipsis;
                            "
                            title="{{ $table }}"
                        >
                            {{ $table }}
                        </div>

                    </div>

                    <x-filament::icon
                        icon="heroicon-m-arrow-up-right"
                        style="
                            height: 1rem;
                            width: 1rem;
                            flex-shrink: 0;
                        "
                        class="text-gray-400"
                    />

                </a>

            @endforeach

        </div>

    </x-filament::section>

@endforeach


{{-- Struktur Database --}}
<x-filament::section
    heading="Struktur Database"
    description="Klik nama tabel untuk melihat struktur kolom."
>

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        @foreach ($this->getAvailableTableGroups() as $group => $tables)

            <div>

                {{-- Nama Group --}}
                <div
                    style="
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                        margin-bottom: 0.75rem;
                    "
                >
                    <x-filament::icon
                        icon="heroicon-m-folder"
                        style="height: 1.1rem; width: 1.1rem;"
                        class="text-primary-500"
                    />

                    <h3
                        style="
                            margin: 0;
                            font-size: 0.95rem;
                            font-weight: 600;
                            color: rgb(31, 41, 55);
                        "
                    >
                        {{ $group }}
                    </h3>

                    <x-filament::badge color="gray">
                        {{ count($tables) }} tabel
                    </x-filament::badge>
                </div>


                {{-- List Tabel --}}
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">

                    @foreach ($tables as $table)

                        <details
                            id="structure-{{ md5($table) }}"
                            style="
                                overflow: hidden;
                                border-radius: 0.75rem;
                                border: 1px solid rgba(156, 163, 175, 0.25);
                                background-color: rgb(255, 255, 255);
                            "
                        >

                            {{-- Header Tabel --}}
                            <summary
                                style="
                                    display: flex;
                                    align-items: center;
                                    justify-content: space-between;
                                    gap: 1rem;
                                    padding: 0.875rem 1rem;
                                    cursor: pointer;
                                    list-style: none;
                                    background-color: rgba(249, 250, 251, 1);
                                "
                            >

                                <div
                                    style="
                                        display: flex;
                                        align-items: center;
                                        gap: 0.75rem;
                                        min-width: 0;
                                    "
                                >

                                    <div
                                        style="
                                            padding: 0.55rem;
                                            border-radius: 0.6rem;
                                            background-color: rgba(59, 130, 246, 0.1);
                                            flex-shrink: 0;
                                        "
                                    >
                                        <x-filament::icon
                                            icon="heroicon-o-table-cells"
                                            style="
                                                height: 1.25rem;
                                                width: 1.25rem;
                                            "
                                            class="text-primary-500"
                                        />
                                    </div>

                                    <div style="min-width: 0;">

                                        <div
                                            style="
                                                font-size: 0.875rem;
                                                font-weight: 600;
                                                color: rgb(17, 24, 39);
                                            "
                                        >
                                            {{ $this->getTableLabel($table) }}
                                        </div>

                                        <div
                                            style="
                                                margin-top: 0.15rem;
                                                font-size: 0.75rem;
                                                color: rgb(107, 114, 128);
                                            "
                                        >
                                            {{ $table }}
                                        </div>

                                    </div>

                                </div>


                                <div
                                    style="
                                        display: flex;
                                        align-items: center;
                                        gap: 0.75rem;
                                        flex-shrink: 0;
                                    "
                                >

                                    <x-filament::badge color="gray">
                                        {{ count($this->getColumns($table)) }} kolom
                                    </x-filament::badge>

                                    <x-filament::icon
                                        icon="heroicon-m-chevron-down"
                                        style="
                                            height: 1.1rem;
                                            width: 1.1rem;
                                        "
                                        class="text-gray-400"
                                    />

                                </div>

                            </summary>


                            {{-- Struktur Kolom --}}
                            <div
                                style="
                                    border-top: 1px solid rgba(156, 163, 175, 0.2);
                                "
                            >

                                <div style="overflow-x: auto;">

                                    <table
                                        style="
                                            width: 100%;
                                            min-width: 700px;
                                            border-collapse: collapse;
                                            font-size: 0.875rem;
                                        "
                                    >

                                        <thead>

                                            <tr
                                                style="
                                                    background-color: rgba(249, 250, 251, 1);
                                                "
                                            >

                                                <th
                                                    style="
                                                        padding: 0.75rem 1rem;
                                                        text-align: left;
                                                        font-size: 0.7rem;
                                                        font-weight: 600;
                                                        color: rgb(107, 114, 128);
                                                        text-transform: uppercase;
                                                        white-space: nowrap;
                                                    "
                                                >
                                                    #
                                                </th>

                                                <th
                                                    style="
                                                        padding: 0.75rem 1rem;
                                                        text-align: left;
                                                        font-size: 0.7rem;
                                                        font-weight: 600;
                                                        color: rgb(107, 114, 128);
                                                        text-transform: uppercase;
                                                        white-space: nowrap;
                                                    "
                                                >
                                                    Nama Kolom
                                                </th>

                                                <th
                                                    style="
                                                        padding: 0.75rem 1rem;
                                                        text-align: left;
                                                        font-size: 0.7rem;
                                                        font-weight: 600;
                                                        color: rgb(107, 114, 128);
                                                        text-transform: uppercase;
                                                        white-space: nowrap;
                                                    "
                                                >
                                                    Tipe
                                                </th>

                                                <th
                                                    style="
                                                        padding: 0.75rem 1rem;
                                                        text-align: left;
                                                        font-size: 0.7rem;
                                                        font-weight: 600;
                                                        color: rgb(107, 114, 128);
                                                        text-transform: uppercase;
                                                        white-space: nowrap;
                                                    "
                                                >
                                                    Nullable
                                                </th>

                                                <th
                                                    style="
                                                        padding: 0.75rem 1rem;
                                                        text-align: left;
                                                        font-size: 0.7rem;
                                                        font-weight: 600;
                                                        color: rgb(107, 114, 128);
                                                        text-transform: uppercase;
                                                        white-space: nowrap;
                                                    "
                                                >
                                                    Key
                                                </th>

                                                <th
                                                    style="
                                                        padding: 0.75rem 1rem;
                                                        text-align: left;
                                                        font-size: 0.7rem;
                                                        font-weight: 600;
                                                        color: rgb(107, 114, 128);
                                                        text-transform: uppercase;
                                                        white-space: nowrap;
                                                    "
                                                >
                                                    Default
                                                </th>

                                                <th
                                                    style="
                                                        padding: 0.75rem 1rem;
                                                        text-align: left;
                                                        font-size: 0.7rem;
                                                        font-weight: 600;
                                                        color: rgb(107, 114, 128);
                                                        text-transform: uppercase;
                                                        white-space: nowrap;
                                                    "
                                                >
                                                    Extra
                                                </th>

                                            </tr>

                                        </thead>


                                        <tbody>

                                            @foreach ($this->getColumns($table) as $index => $column)

                                                <tr
                                                    style="
                                                        border-top: 1px solid rgba(156, 163, 175, 0.15);
                                                    "
                                                >

                                                    {{-- Nomor --}}
                                                    <td
                                                        style="
                                                            padding: 0.75rem 1rem;
                                                            color: rgb(156, 163, 175);
                                                        "
                                                    >
                                                        {{ $index + 1 }}
                                                    </td>


                                                    {{-- Nama Kolom --}}
                                                    <td
                                                        style="
                                                            padding: 0.75rem 1rem;
                                                            font-weight: 600;
                                                            color: rgb(31, 41, 55);
                                                            white-space: nowrap;
                                                        "
                                                    >
                                                        {{ $column['Field'] }}
                                                    </td>


                                                    {{-- Tipe --}}
                                                    <td style="padding: 0.75rem 1rem;">

                                                        <code
                                                            style="
                                                                padding: 0.25rem 0.5rem;
                                                                border-radius: 0.375rem;
                                                                background-color: rgb(243, 244, 246);
                                                                font-size: 0.75rem;
                                                                color: rgb(55, 65, 81);
                                                                white-space: nowrap;
                                                            "
                                                        >
                                                            {{ $column['Type'] }}
                                                        </code>

                                                    </td>


                                                    {{-- Nullable --}}
                                                    <td style="padding: 0.75rem 1rem;">

                                                        @if ($column['Null'] === 'YES')

                                                            <x-filament::badge color="success">
                                                                YES
                                                            </x-filament::badge>

                                                        @else

                                                            <x-filament::badge color="danger">
                                                                NO
                                                            </x-filament::badge>

                                                        @endif

                                                    </td>


                                                    {{-- Key --}}
                                                    <td style="padding: 0.75rem 1rem;">

                                                        @if ($column['Key'] === 'PRI')

                                                            <x-filament::badge color="info">
                                                                PRIMARY
                                                            </x-filament::badge>

                                                        @elseif ($column['Key'] === 'UNI')

                                                            <x-filament::badge color="success">
                                                                UNIQUE
                                                            </x-filament::badge>

                                                        @elseif ($column['Key'] === 'MUL')

                                                            <x-filament::badge color="warning">
                                                                INDEX
                                                            </x-filament::badge>

                                                        @else

                                                            <span style="color: rgb(156, 163, 175);">
                                                                -
                                                            </span>

                                                        @endif

                                                    </td>


                                                    {{-- Default --}}
                                                    <td
                                                        style="
                                                            padding: 0.75rem 1rem;
                                                            color: rgb(75, 85, 99);
                                                            white-space: nowrap;
                                                        "
                                                    >
                                                        {{ $column['Default'] ?? 'NULL' }}
                                                    </td>


                                                    {{-- Extra --}}
                                                    <td
                                                        style="
                                                            padding: 0.75rem 1rem;
                                                            color: rgb(75, 85, 99);
                                                            white-space: nowrap;
                                                        "
                                                    >
                                                        {{ $column['Extra'] ?: '-' }}
                                                    </td>

                                                </tr>

                                            @endforeach

                                        </tbody>

                                    </table>

                                </div>

                            </div>

                        </details>

                    @endforeach

                </div>

            </div>

        @endforeach

    </div>

</x-filament::section>

</x-filament-panels::page>
