<?php

namespace App\Filament\Resources\Traits;

use App\Models\Kerjasama;
use App\Models\MasterKota;
use App\Models\MasterProvinsi;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Collection;

trait HasKerjasamaLocationFormSchema
{
    public static function getKerjasamaLocationFormSchema(): array
    {
        return [
            Forms\Components\Select::make('provinsi_id')
                ->label('Provinsi Kerjasama')
                ->options(fn (): Collection => MasterProvinsi::query()->orderBy('nama_provinsi')->pluck('nama_provinsi', 'id'))
                ->searchable()
                ->preload()
                ->live()
                ->createOptionForm([
                    Forms\Components\TextInput::make('nama_provinsi')
                        ->label('Nama Provinsi')
                        ->required()
                        ->maxLength(255),
                ])
                ->createOptionUsing(fn (array $data): int => MasterProvinsi::create($data)->getKey())
                ->afterStateUpdated(fn (Set $set) => $set('kota_id', null))
                ->afterStateHydrated(function (Forms\Components\Select $component, ?Kerjasama $record): void {
                    if ($component->getState() === null && $record?->mitra) {
                        $component->state($record->mitra->provinsi_id);
                    }
                }),
            Forms\Components\Select::make('kota_id')
                ->label('Kota Kerjasama')
                ->options(fn (Get $get): Collection => MasterKota::query()
                    ->where('provinsi_id', $get('provinsi_id'))
                    ->orderBy('nama_kota')
                    ->pluck('nama_kota', 'id'))
                ->searchable()
                ->preload()
                ->disabled(fn (Get $get): bool => ! $get('provinsi_id'))
                ->createOptionForm([
                    Forms\Components\TextInput::make('nama_kota')
                        ->label('Nama Kota')
                        ->required()
                        ->maxLength(255),
                ])
                ->createOptionUsing(function (array $data, Get $get): int {
                    return MasterKota::create([
                        'provinsi_id' => $get('../../provinsi_id') ?: $get('provinsi_id'),
                        'nama_kota' => $data['nama_kota'],
                    ])->getKey();
                })
                ->afterStateHydrated(function (Forms\Components\Select $component, ?Kerjasama $record): void {
                    if ($component->getState() === null && $record?->mitra) {
                        $component->state($record->mitra->kota_id);
                    }
                }),
        ];
    }
}