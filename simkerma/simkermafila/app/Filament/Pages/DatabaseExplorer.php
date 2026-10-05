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
     * Filter tabel berdasarkan kata kunci.
     */
    public string $searchTable = '';

    public function updatedSearchTable(): void
    {
        // Reset state ketika filter berubah
        $this->dataPage = 1;
    }

    public function getAvailableTableGroups(): array
    {
        $schema = Schema::connection(DB::getDefaultConnection());

        $existingTables = $schema->getTableListing(
            schema: $this->getDatabase(),
            schemaQualified: false,
        );

        $needle = trim(mb_strtolower($this->searchTable));

        return collect($this->getTableGroups())
            ->map(fn (array $tables) => array_values(
                array_intersect($tables, $existingTables)
            ))
            ->map(function (array $tables) use ($needle) {
                if ($needle === '') {
                    return $tables;
                }

                return array_values(array_filter(
                    $tables,
                    fn (string $table) => str_contains(
                        mb_strtolower($table),
                        $needle
                    )
                ));
            })
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

    public function getColumns(string $table): array
    {
        if (! in_array($table, $this->getTables(), true)) {
            throw new RuntimeException('Tabel tidak diizinkan.');
        }

        $database = str_replace('`', '``', $this->getDatabase());
        $table = str_replace('`', '``', $table);

        return array_map(
            fn ($column) => (array) $column,
            DB::select("SHOW COLUMNS FROM `{$database}`.`{$table}`")
        );
    }

    public function getTableLabel(string $table): string
    {
        return Str::headline($table);
    }

    public ?string $selectedTable = null;

    public bool $showDataModal = false;

    public int $dataPage = 1;

    public int $perPage = 10;

    /** Filter kata kunci di dalam isi tabel. */
    public string $searchData = '';

    public function updatedSearchData(): void
    {
        $this->dataPage = 1;
    }

    public function updatedPerPage(): void
    {
        $this->dataPage = 1;
    }

    public function viewTableData(string $table): void
    {
        if (! in_array($table, $this->getTables(), true)) {
            throw new RuntimeException('Tabel tidak diizinkan.');
        }

        $this->selectedTable = $table;
        $this->dataPage = 1;
        $this->searchData = '';
        $this->showDataModal = true;
    }

    public function closeDataModal(): void
    {
        $this->showDataModal = false;
        $this->selectedTable = null;
        $this->searchData = '';
        $this->dataPage = 1;
    }

    public function getTableData(): array
    {
        if (
            ! $this->selectedTable ||
            ! in_array($this->selectedTable, $this->getTables(), true)
        ) {
            return [
                'columns' => [],
                'rows' => [],
                'total' => 0,
            ];
        }

        $table = $this->selectedTable;
        $columns = $this->getColumns($table);
        $columnNames = array_column($columns, 'Field');

        $query = DB::table($table);

        $needle = trim($this->searchData);
        if ($needle !== '' && count($columnNames) > 0) {
            $query->where(function ($q) use ($columnNames, $needle) {
                foreach ($columnNames as $col) {
                    $q->orWhere($col, 'like', '%' . $needle . '%');
                }
            });
        }

        $total = (clone $query)->count();

        $rows = (clone $query)
            ->offset(($this->dataPage - 1) * $this->perPage)
            ->limit($this->perPage)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return [
            'columns' => $columnNames,
            'rows' => $rows,
            'total' => $total,
        ];
    }

    public function changeDataPage(int $page): void
    {
        $data = $this->getTableData();
        $lastPage = max(1, (int) ceil($data['total'] / $this->perPage));

        $this->dataPage = max(1, min($page, $lastPage));
    }

    public function previousPage(): void
    {
        $this->changeDataPage($this->dataPage - 1);
    }

    public function nextPage(): void
    {
        $this->changeDataPage($this->dataPage + 1);
    }

    /**
     * Download SQL untuk satu tabel.
     */
    public function downloadTableSql(string $table)
    {
        if (! in_array($table, $this->getTables(), true)) {
            throw new RuntimeException('Tabel tidak diizinkan.');
        }

        $database = str_replace('`', '``', $this->getDatabase());
        $escapedTable = str_replace('`', '``', $table);

        $create = DB::select(
            "SHOW CREATE TABLE `{$database}`.`{$escapedTable}`"
        );

        $createSql = (array) $create[0];
        $createSql = end($createSql);

        $filename = $table . '_' . now()->format('Ymd_His') . '.sql';

        return response()->streamDownload(function () use (
            $table,
            $createSql
        ) {
            echo "-- Database: " . $this->getDatabase() . PHP_EOL;
            echo "-- Table: {$table}" . PHP_EOL . PHP_EOL;
            echo "SET FOREIGN_KEY_CHECKS=0;" . PHP_EOL . PHP_EOL;
            echo "DROP TABLE IF EXISTS `{$table}`;" . PHP_EOL;
            echo $createSql . ';' . PHP_EOL . PHP_EOL;

            $this->streamTableRows($table);

            echo PHP_EOL . "SET FOREIGN_KEY_CHECKS=1;" . PHP_EOL;
        }, $filename, [
            'Content-Type' => 'application/sql; charset=UTF-8',
        ]);
    }

    /**
     * Download SQL untuk seluruh tabel yang diizinkan.
     */
    public function downloadAllSql()
    {
        $tables = $this->getTables();
        $database = str_replace('`', '``', $this->getDatabase());
        $filename = 'database_' . $this->getDatabase() . '_'
            . now()->format('Ymd_His') . '.sql';

        return response()->streamDownload(function () use (
            $tables,
            $database
        ) {
            echo "-- ============================================" . PHP_EOL;
            echo "-- Database : " . $this->getDatabase() . PHP_EOL;
            echo "-- Generated: " . now()->toDateTimeString() . PHP_EOL;
            echo "-- Tables   : " . count($tables) . PHP_EOL;
            echo "-- ============================================" . PHP_EOL . PHP_EOL;
            echo "SET FOREIGN_KEY_CHECKS=0;" . PHP_EOL;
            echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";" . PHP_EOL;
            echo "SET time_zone = \"+00:00\";" . PHP_EOL . PHP_EOL;
            echo "START TRANSACTION;" . PHP_EOL . PHP_EOL;

            foreach ($tables as $table) {
                $escapedTable = str_replace('`', '``', $table);

                echo "-- --------------------------------------------" . PHP_EOL;
                echo "-- Table structure for `{$table}`" . PHP_EOL;
                echo "-- --------------------------------------------" . PHP_EOL;

                $create = DB::select(
                    "SHOW CREATE TABLE `{$database}`.`{$escapedTable}`"
                );
                $createSql = (array) $create[0];
                $createSql = end($createSql);

                echo "DROP TABLE IF EXISTS `{$table}`;" . PHP_EOL;
                echo $createSql . ';' . PHP_EOL . PHP_EOL;

                echo "-- Dumping data for `{$table}`" . PHP_EOL;

                $this->streamTableRows($table);

                echo PHP_EOL . PHP_EOL;
            }

            echo "COMMIT;" . PHP_EOL;
            echo "SET FOREIGN_KEY_CHECKS=1;" . PHP_EOL;
        }, $filename, [
            'Content-Type' => 'application/sql; charset=UTF-8',
        ]);
    }

    /**
     * Helper: streaming INSERT statements untuk satu tabel.
     */
    protected function streamTableRows(string $table): void
    {
        $columnListing = DB::getSchemaBuilder()->getColumnListing($table);

        if (empty($columnListing)) {
            return;
        }

        $orderBy = in_array('id', $columnListing, true)
            ? 'id'
            : $columnListing[0];

        DB::table($table)->orderBy($orderBy)->chunk(500, function ($rows) use ($table) {
            foreach ($rows as $row) {
                $values = array_map(function ($value) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    if (is_bool($value)) {
                        return $value ? '1' : '0';
                    }

                    return DB::connection()->getPdo()->quote((string) $value);
                }, array_values((array) $row));

                $columns = array_map(
                    fn ($column) => '`' . str_replace('`', '``', $column) . '`',
                    array_keys((array) $row)
                );

                echo 'INSERT INTO `' . $table . '` ('
                    . implode(', ', $columns)
                    . ') VALUES ('
                    . implode(', ', $values)
                    . ');' . PHP_EOL;
            }
        });
    }
}