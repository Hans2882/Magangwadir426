<x-filament-panels::page>
    <x-filament::section>
        {{-- Database --}}
        <div style="
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        ">
            <x-filament::icon
                icon="heroicon-o-circle-stack"
                style="
                    height: 1.25rem;
                    width: 1.25rem;
                    flex-shrink: 0;
                "
                class="text-primary-500"
            />

            <span style="
                font-weight: 600;
                font-size: 0.875rem;
            ">
                {{ $this->getDatabase() }}
            </span>
        </div>

        {{-- Daftar tabel --}}
        <div style="
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        ">
            @foreach ($this->getTables() as $table)
                <a
                    href="#table-{{ md5($table) }}"
                    style="
                        padding: 0.625rem 0.75rem;
                        border-radius: 0.5rem;
                        border: 1px solid rgba(156, 163, 175, 0.2);
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                        overflow: hidden;
                        background-color: rgb(255, 255, 255);
                        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                        text-decoration: none;
                    "
                >
                    <x-filament::icon
                        icon="heroicon-o-table-cells"
                        style="
                            height: 1rem;
                            width: 1rem;
                            flex-shrink: 0;
                        "
                        class="text-gray-400"
                    />

                    <span style="
                        font-size: 0.75rem;
                        font-weight: 500;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    ">
                        {{ $table }}
                    </span>
                </a>
            @endforeach
        </div>
    </x-filament::section>

    {{-- Struktur tabel kerjasama --}}
    <x-filament::section
        heading="Struktur Tabel kerjasama"
        description="Informasi kolom pada tabel kerjasama"
    >
        <div style="overflow-x: auto;">
            <table style="
                width: 100%;
                border-collapse: collapse;
                font-size: 0.875rem;
            ">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 0.75rem; border-bottom: 1px solid #e5e7eb;">
                            #
                        </th>

                        <th style="text-align: left; padding: 0.75rem; border-bottom: 1px solid #e5e7eb;">
                            Nama Kolom
                        </th>

                        <th style="text-align: left; padding: 0.75rem; border-bottom: 1px solid #e5e7eb;">
                            Tipe
                        </th>

                        <th style="text-align: left; padding: 0.75rem; border-bottom: 1px solid #e5e7eb;">
                            Nullable
                        </th>

                        <th style="text-align: left; padding: 0.75rem; border-bottom: 1px solid #e5e7eb;">
                            Key
                        </th>

                        <th style="text-align: left; padding: 0.75rem; border-bottom: 1px solid #e5e7eb;">
                            Default
                        </th>

                        <th style="text-align: left; padding: 0.75rem; border-bottom: 1px solid #e5e7eb;">
                            Extra
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($this->getColumns('kerjasama') as $index => $column)
                        <tr>
                            <td style="
                                padding: 0.75rem;
                                border-bottom: 1px solid #f3f4f6;
                                color: #9ca3af;
                            ">
                                {{ $index + 1 }}
                            </td>

                            <td style="
                                padding: 0.75rem;
                                border-bottom: 1px solid #f3f4f6;
                                font-weight: 600;
                            ">
                                {{ $column['Field'] }}
                            </td>

                            <td style="
                                padding: 0.75rem;
                                border-bottom: 1px solid #f3f4f6;
                            ">
                                {{ $column['Type'] }}
                            </td>

                            <td style="
                                padding: 0.75rem;
                                border-bottom: 1px solid #f3f4f6;
                            ">
                                @if ($column['Null'] === 'YES')
                                    <span style="color: #16a34a;">
                                        YES
                                    </span>
                                @else
                                    <span style="color: #dc2626;">
                                        NO
                                    </span>
                                @endif
                            </td>

                            <td style="
                                padding: 0.75rem;
                                border-bottom: 1px solid #f3f4f6;
                                font-weight: 600;
                            ">
                                @if ($column['Key'] === 'PRI')
                                    <span style="color: #2563eb;">
                                        PRIMARY
                                    </span>
                                @elseif ($column['Key'] === 'UNI')
                                    <span style="color: #7c3aed;">
                                        UNIQUE
                                    </span>
                                @elseif ($column['Key'] === 'MUL')
                                    <span style="color: #d97706;">
                                        INDEX
                                    </span>
                                @else
                                    -
                                @endif
                            </td>

                            <td style="
                                padding: 0.75rem;
                                border-bottom: 1px solid #f3f4f6;
                            ">
                                {{ $column['Default'] ?? '-' }}
                            </td>

                            <td style="
                                padding: 0.75rem;
                                border-bottom: 1px solid #f3f4f6;
                            ">
                                {{ $column['Extra'] ?: '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>