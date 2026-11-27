<?php

namespace App\Filament\Resources\Traits;

use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasAcademicUnitTableColumns
{
    /**
     * Compact, expandable table column for Program Studi badges.
     */
    public static function getProdisTableColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('prodis')
            ->label('Program Studi')
            ->badge()
            ->listWithLineBreaks()
            ->limitList(1)
            ->expandableLimitedList()
            ->limit(35)
            ->getStateUsing(fn (Model $record): array => static::extractProdiNames($record))
            ->tooltip(function (Model $record): ?string {
                $names = static::extractProdiNames($record);

                return empty($names) ? null : implode(', ', $names);
            })
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->whereHas(
                    'prodis',
                    fn (Builder $q) => $q->where('nama_prodi', 'like', "%{$search}%")
                );
            })
            ->default('-');
    }

    /**
     * Compact, expandable table column for Jurusan badges (with prodi.jurusan fallback).
     */
    public static function getJurusansTableColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('jurusans')
            ->label('Jurusan')
            ->badge()
            ->listWithLineBreaks()
            ->limitList(1)
            ->expandableLimitedList()
            ->limit(30)
            ->getStateUsing(fn (Model $record): array => static::extractJurusanNames($record))
            ->tooltip(function (Model $record): ?string {
                $names = static::extractJurusanNames($record);

                return empty($names) ? null : implode(', ', $names);
            })
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->where(function (Builder $sub) use ($search) {
                    $sub->whereHas(
                        'jurusans',
                        fn (Builder $q) => $q->where('nama_jurusan', 'like', "%{$search}%")
                    )->orWhereHas(
                        'prodis.jurusan',
                        fn (Builder $q) => $q->where('nama_jurusan', 'like', "%{$search}%")
                    );
                });
            })
            ->default('-');
    }

    /**
     * Collect unique, trimmed study program names for a record.
     *
     * @return array<int, string>
     */
    private static function extractProdiNames(Model $record): array
    {
        return $record->prodis
            ->pluck('nama_prodi')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Collect unique, trimmed department names directly or derived from prodis.
     *
     * @return array<int, string>
     */
    private static function extractJurusanNames(Model $record): array
    {
        return $record->jurusans
            ->pluck('nama_jurusan')
            ->merge($record->prodis->map(fn ($prodi) => $prodi->jurusan?->nama_jurusan))
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
