<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class DatabaseExplorer extends Page
{
    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedCircleStack;

    protected static ?string $navigationLabel = 'Database Explorer';

    protected static ?string $title = 'Database Explorer';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.database-explorer';

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return (bool) (
            $user?->userPrivilege?->privilege?->is_admin_panel
            ?? false
        );
    }

    public function getDatabase(): string
    {
        return DB::connection()->getDatabaseName();
    }

    /**
     * Kelompok tabel yang ditampilkan.
     */
    public function getTableGroups(): array
    {
        return [
            'Data Utama' => [
                'mitra',
                'kerjasama',
                'kerjasama_jurusan',
                'kerjasama_prodi',
                'usulan_kerjasamas',
                'usulan_kegiatans',
            ],

            'Data Pendukung' => [
                'master_jenis_dokumen',
                'master_mitra_iku',
                'master_negara',
                'master_provinsi',
                'master_kota',
                'master_jurusans',
                'master_program_studi',
                'master_kegiatan',
            ],

            'Penilaian dan Evaluasi' => [
                'mitra_award_periods',
                'mitra_award_scores',
                'kuisioner_kepuasan',
                'kuisioner_kepuasan_followup',
            ],

            'Pengguna dan Sistem' => [
                'users',
                'privileges',
                'user_privileges',
                'user_program_studi',
                'api_keys',
                'notifications',
                'personal_access_tokens',
                'sessions',
            ],
        ];
    }

    /**
     * Hanya tampilkan tabel yang benar-benar tersedia.
     */
    public function getAvailableTableGroups(): array
{
    $schema = Schema::connection(DB::getDefaultConnection());

    $existingTables = $schema->getTableListing(
        schema: $this->getDatabase(),
        schemaQualified: false,
    );

    return collect($this->getTableGroups())
        ->map(fn (array $tables) => array_values(
            array_intersect($tables, $existingTables)
        ))
        ->filter(fn (array $tables) => count($tables) > 0)
        ->all();
}

    public function getTables(): array
    {
        return collect($this->getAvailableTableGroups())
            ->flatten()
            ->values()
            ->all();
    }

    /**
     * Mengambil struktur tabel berdasarkan daftar yang diizinkan.
     */
    public function getColumns(string $table): array
    {
        if (! in_array($table, $this->getTables(), true)) {
            throw new RuntimeException('Tabel tidak diizinkan.');
        }

        $database = str_replace('`', '``', $this->getDatabase());
        $table = str_replace('`', '``', $table);

        return array_map(
            fn ($column) => (array) $column,
            DB::select(
                "SHOW COLUMNS FROM `{$database}`.`{$table}`"
            )
        );
    }

    public function getTableLabel(string $table): string
    {
        return Str::headline($table);
    }
}