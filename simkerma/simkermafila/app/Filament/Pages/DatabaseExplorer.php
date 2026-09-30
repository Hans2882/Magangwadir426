<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        return $user?->userPrivilege?->privilege?->is_admin_panel ?? false;
    }

    public function getDatabase(): string
    {
        return 'simkerma2';
    }

    public function getTables(): array
    {
        $database = $this->getDatabase();

        return collect(
            DB::select("SHOW TABLES FROM `{$database}`")
        )
            ->map(fn ($table) => array_values((array) $table)[0])
            ->values()
            ->all();
    }

    public function getColumns(string $table): array
    {
        $database = $this->getDatabase();

        $table = str_replace('`', '``', $table);

        return array_map(
            fn ($column) => (array) $column,
            DB::select(
                "SHOW COLUMNS FROM `{$database}`.`{$table}`"
            )
        );
    }
}