<x-filament-panels::page>
    {{-- Header Database --}}
    <x-filament::section>
        <div style="display:flex;align-items:center;gap:.75rem;">
            <div style="padding:.75rem;border-radius:.75rem;background:rgba(59,130,246,.1);">
                <x-filament::icon
                    icon="heroicon-o-circle-stack"
                    style="height:1.5rem;width:1.5rem;"
                    class="text-primary-500"
                />
            </div>
            <div>
                <h2 style="font-size:1.125rem;font-weight:700;">
                    Database Explorer
                </h2>
                <p style="font-size:.8rem;color:#6b7280;">
                    Lihat struktur, isi tabel, dan unduh query SQL.
                </p>
            </div>
        </div>
    </x-filament::section>

    {{-- Navigasi Tabel --}}
    @foreach ($this->getAvailableTableGroups() as $group => $tables)
        <x-filament::section :heading="$group">
            <div style="
                display:grid;
                grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
                gap:.75rem;
            ">
                @foreach ($tables as $table)
                    <a
                        href="#structure-{{ md5($table) }}"
                        style="
                            display:flex;
                            align-items:center;
                            gap:.75rem;
                            min-width:0;
                            padding:.875rem;
                            border-radius:.75rem;
                            border:1px solid rgba(156,163,175,.25);
                            background:white;
                            text-decoration:none;
                            transition:all .2s ease;
                        "
                        onmouseover="this.style.borderColor='rgb(59,130,246)'"
                        onmouseout="this.style.borderColor='rgba(156,163,175,.25)'"
                    >
                        <div style="
                            padding:.6rem;
                            border-radius:.6rem;
                            background:rgba(59,130,246,.1);
                            flex-shrink:0;
                        ">
                            <x-filament::icon
                                icon="heroicon-o-table-cells"
                                style="height:1.25rem;width:1.25rem;"
                                class="text-primary-500"
                            />
                        </div>

                        <div style="min-width:0;flex:1;">
                            <div style="
                                font-size:.875rem;
                                font-weight:600;
                                color:#1f2937;
                                white-space:nowrap;
                                overflow:hidden;
                                text-overflow:ellipsis;
                            ">
                                {{ $this->getTableLabel($table) }}
                            </div>
                            <div style="
                                margin-top:.15rem;
                                font-size:.75rem;
                                color:#6b7280;
                                white-space:nowrap;
                                overflow:hidden;
                                text-overflow:ellipsis;
                            ">
                                {{ $table }}
                            </div>
                        </div>

                        <x-filament::icon
                            icon="heroicon-m-arrow-up-right"
                            style="height:1rem;width:1rem;flex-shrink:0;"
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
        description="Klik nama tabel untuk melihat struktur kolom atau gunakan tombol untuk melihat dan mengunduh data."
    >
        <div style="display:flex;flex-direction:column;gap:1.5rem;">
            @foreach ($this->getAvailableTableGroups() as $group => $tables)
                <div>
                    {{-- Nama Grup --}}
                    <div style="
                        display:flex;
                        align-items:center;
                        gap:.5rem;
                        margin-bottom:.75rem;
                    ">
                        <x-filament::icon
                            icon="heroicon-m-folder"
                            style="height:1.1rem;width:1.1rem;"
                            class="text-primary-500"
                        />
                        <h3 style="
                            margin:0;
                            font-size:.95rem;
                            font-weight:600;
                            color:#1f2937;
                        ">
                            {{ $group }}
                        </h3>
                        <x-filament::badge color="gray">
                            {{ count($tables) }} tabel
                        </x-filament::badge>
                    </div>

                    {{-- Daftar Tabel --}}
                    <div style="display:flex;flex-direction:column;gap:.75rem;">
                        @foreach ($tables as $table)
                            <details
                                id="structure-{{ md5($table) }}"
                                style="
                                    overflow:hidden;
                                    border-radius:.75rem;
                                    border:1px solid rgba(156,163,175,.25);
                                    background:white;
                                "
                            >
                                {{-- Header Tabel --}}
                                <summary style="
                                    display:flex;
                                    align-items:center;
                                    justify-content:space-between;
                                    gap:1rem;
                                    padding:.875rem 1rem;
                                    cursor:pointer;
                                    list-style:none;
                                    background:#f9fafb;
                                ">
                                    <div style="
                                        display:flex;
                                        align-items:center;
                                        gap:.75rem;
                                        min-width:0;
                                        flex:1;
                                    ">
                                        <div style="
                                            padding:.55rem;
                                            border-radius:.6rem;
                                            background:rgba(59,130,246,.1);
                                            flex-shrink:0;
                                        ">
                                            <x-filament::icon
                                                icon="heroicon-o-table-cells"
                                                style="height:1.25rem;width:1.25rem;"
                                                class="text-primary-500"
                                            />
                                        </div>

                                        <div style="min-width:0;">
                                            <div style="
                                                font-size:.875rem;
                                                font-weight:600;
                                                color:#111827;
                                            ">
                                                {{ $this->getTableLabel($table) }}
                                            </div>
                                            <div style="
                                                margin-top:.15rem;
                                                font-size:.75rem;
                                                color:#6b7280;
                                            ">
                                                {{ $table }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Tombol Aksi --}}
                                    <div
                                        style="
                                            display:flex;
                                            align-items:center;
                                            gap:.5rem;
                                            flex-shrink:0;
                                            flex-wrap:wrap;
                                        "
                                        onclick="event.stopPropagation()"
                                    >
                                        <x-filament::badge color="gray">
                                            {{ count($this->getColumns($table)) }} kolom
                                        </x-filament::badge>

                                        <x-filament::button
                                            size="xs"
                                            color="info"
                                            icon="heroicon-o-eye"
                                            wire:click.stop="viewTableData('{{ $table }}')"
                                        >
                                            Lihat Data
                                        </x-filament::button>

                                        <x-filament::button
                                            size="xs"
                                            color="success"
                                            icon="heroicon-o-arrow-down-tray"
                                            wire:click.stop="downloadTableSql('{{ $table }}')"
                                        >
                                            Download SQL
                                        </x-filament::button>

                                        <x-filament::icon
                                            icon="heroicon-m-chevron-down"
                                            style="height:1.1rem;width:1.1rem;"
                                            class="text-gray-400"
                                        />
                                    </div>
                                </summary>

                                {{-- Struktur Kolom --}}
                                <div style="border-top:1px solid rgba(156,163,175,.2);">
                                    <div style="overflow-x:auto;">
                                        <table style="
                                            width:100%;
                                            min-width:700px;
                                            border-collapse:collapse;
                                            font-size:.875rem;
                                        ">
                                            <thead>
                                                <tr style="background:#f9fafb;">
                                                    @foreach ([
                                                        '#' => '80px',
                                                        'Nama Kolom' => 'auto',
                                                        'Tipe' => 'auto',
                                                        'Nullable' => 'auto',
                                                        'Key' => 'auto',
                                                        'Default' => 'auto',
                                                        'Extra' => 'auto',
                                                    ] as $heading => $width)
                                                        <th style="
                                                            padding:.75rem 1rem;
                                                            text-align:left;
                                                            font-size:.7rem;
                                                            font-weight:600;
                                                            color:#6b7280;
                                                            text-transform:uppercase;
                                                            white-space:nowrap;
                                                        ">
                                                            {{ $heading }}
                                                        </th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($this->getColumns($table) as $index => $column)
                                                    <tr style="border-top:1px solid rgba(156,163,175,.15);">
                                                        <td style="padding:.75rem 1rem;color:#9ca3af;">
                                                            {{ $index + 1 }}
                                                        </td>
                                                        <td style="
                                                            padding:.75rem 1rem;
                                                            font-weight:600;
                                                            color:#1f2937;
                                                            white-space:nowrap;
                                                        ">
                                                            {{ $column['Field'] }}
                                                        </td>
                                                        <td style="padding:.75rem 1rem;">
                                                            <code style="
                                                                padding:.25rem .5rem;
                                                                border-radius:.375rem;
                                                                background:#f3f4f6;
                                                                font-size:.75rem;
                                                                color:#374151;
                                                                white-space:nowrap;
                                                            ">
                                                                {{ $column['Type'] }}
                                                            </code>
                                                        </td>
                                                        <td style="padding:.75rem 1rem;">
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
                                                        <td style="padding:.75rem 1rem;">
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
                                                                <span style="color:#9ca3af;">-</span>
                                                            @endif
                                                        </td>
                                                        <td style="
                                                            padding:.75rem 1rem;
                                                            color:#4b5563;
                                                            white-space:nowrap;
                                                        ">
                                                            {{ $column['Default'] ?? 'NULL' }}
                                                        </td>
                                                        <td style="
                                                            padding:.75rem 1rem;
                                                            color:#4b5563;
                                                            white-space:nowrap;
                                                        ">
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

    {{-- Modal Lihat Data --}}
    @if ($showDataModal && $selectedTable)
        @php
            $tableData = $this->getTableData();
            $lastPage = max(
                1,
                (int) ceil(
                    $tableData['total'] / max(1, $perPage)
                )
            );
        @endphp

        <div
            x-data
            x-on:keydown.escape.window="$wire.set('showDataModal', false)"
            wire:key="table-data-modal-{{ $selectedTable }}"
            style="
                position:fixed;
                inset:0;
                z-index:100;
                display:flex;
                align-items:center;
                justify-content:center;
                padding:1rem;
                background:rgba(0,0,0,.55);
            "
        >
            <div
                wire:click.stop
                style="
                    width:100%;
                    max-width:1100px;
                    max-height:85vh;
                    overflow:auto;
                    box-sizing:border-box;
                    padding:1.25rem;
                    border-radius:1rem;
                    background:white;
                    color:#1f2937;
                "
            >
                {{-- Header Modal --}}
                <div style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    gap:1rem;
                    margin-bottom:1rem;
                ">
                    <div>
                        <h2 style="font-size:1.125rem;font-weight:700;">
                            Isi Tabel: {{ $selectedTable }}
                        </h2>
                        <p style="font-size:.8rem;color:#6b7280;">
                            Total {{ number_format($tableData['total']) }} baris
                        </p>
                    </div>

                    <x-filament::icon-button
                        icon="heroicon-m-x-mark"
                        color="gray"
                        wire:click="$set('showDataModal', false)"
                        label="Tutup"
                    />
                </div>

                {{-- Pilihan Jumlah Baris --}}
                <div style="
                    display:flex;
                    align-items:center;
                    justify-content:space-between;
                    flex-wrap:wrap;
                    gap:.75rem;
                    margin-bottom:1rem;
                ">
                    <div style="font-size:.8rem;color:#6b7280;">
                        Halaman {{ $page ?? 1 }} dari {{ $lastPage }}
                    </div>

                    <div style="display:flex;align-items:center;gap:.5rem;">
                        <label for="database-per-page" style="font-size:.8rem;">
                            Baris per halaman
                        </label>
                        <select
                            id="database-per-page"
                            wire:model.live="perPage"
                            style="
                                padding:.4rem .6rem;
                                border:1px solid #d1d5db;
                                border-radius:.5rem;
                                background:white;
                                color:#1f2937;
                            "
                        >
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>

                {{-- Isi Tabel --}}
                @if (count($tableData['columns']))
                    <div style="
                        width:100%;
                        max-width:100%;
                        overflow:auto;
                        border:1px solid #e5e7eb;
                        border-radius:.5rem;
                        -webkit-overflow-scrolling:touch;
                    ">
                        <table style="
                            width:max-content;
                            min-width:100%;
                            table-layout:auto;
                            border-collapse:collapse;
                            font-size:.8rem;
                            background:white;
                        ">
                            <thead>
                                <tr style="background:#f3f4f6;">
                                    @foreach ($tableData['columns'] as $column)
                                        <th style="
                                            padding:.75rem;
                                            text-align:left;
                                            vertical-align:top;
                                            border:1px solid #e5e7eb;
                                            font-weight:600;
                                            white-space:nowrap;
                                            min-width:120px;
                                            max-width:250px;
                                        ">
                                            {{ $column }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($tableData['rows'] as $row)
                                    <tr>
                                        @foreach ($tableData['columns'] as $column)
                                            <td style="
                                                padding:.75rem;
                                                border:1px solid #e5e7eb;
                                                vertical-align:top;
                                                white-space:normal;
                                                overflow-wrap:anywhere;
                                                word-break:break-word;
                                                min-width:120px;
                                                max-width:250px;
                                            ">
                                                @if (($row[$column] ?? null) === null)
                                                    <span style="color:#9ca3af;">
                                                        NULL
                                                    </span>
                                                @else
                                                    {{ $row[$column] }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td
                                            colspan="{{ count($tableData['columns']) }}"
                                            style="
                                                padding:1rem;
                                                text-align:center;
                                                color:#6b7280;
                                            "
                                        >
                                            Belum ada data.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="
                        padding:2rem;
                        text-align:center;
                        color:#6b7280;
                    ">
                        Tidak ada kolom atau data yang dapat ditampilkan.
                    </div>
                @endif

                {{-- Pagination --}}
                <div style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    flex-wrap:wrap;
                    gap:.75rem;
                    margin-top:1rem;
                ">
                    <div style="font-size:.8rem;color:#6b7280;">
                        Menampilkan
                        {{ count($tableData['rows']) }}
                        dari
                        {{ number_format($tableData['total']) }}
                        baris
                    </div>

                    <div style="display:flex;align-items:center;gap:.5rem;">
                        <x-filament::button
                            size="sm"
                            color="gray"
                            outlined
                            icon="heroicon-m-chevron-left"
                            wire:click="previousPage"
                            :disabled="($page ?? 1) <= 1"
                        >
                            Sebelumnya
                        </x-filament::button>

                        <span style="
                            font-size:.8rem;
                            color:#374151;
                            white-space:nowrap;
                        ">
                            {{ $page ?? 1 }} / {{ $lastPage }}
                        </span>

                        <x-filament::button
                            size="sm"
                            color="gray"
                            outlined
                            icon="heroicon-m-chevron-right"
                            icon-position="after"
                            wire:click="nextPage"
                            :disabled="($page ?? 1) >= $lastPage"
                        >
                            Berikutnya
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>