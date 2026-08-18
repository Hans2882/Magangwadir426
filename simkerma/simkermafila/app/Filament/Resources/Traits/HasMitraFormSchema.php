<?php

namespace App\Filament\Resources\Traits;

use Filament\Forms;
use Filament\Schemas\Schema;

trait HasMitraFormSchema
{
    /**
     * Get form schema for creating new Mitra from related resources
     */
    public static function getMitraCreateFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('nama_mitra')
                ->label('Nama Mitra')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('negara_id')
                ->label('Negara')
                ->relationship('negara', 'nama_negara')
                ->searchable()
                ->preload()
                ->optionsLimit(200)
                ->hint('Kosongkan untuk Mitra Dalam Negeri (Indonesia)'),
            Forms\Components\Select::make('kategori_id')
                ->label('Kategori (IKU)')
                ->relationship('kategori', 'kategori')
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('telepon')
                ->label('Nomor Telepon')
                ->maxLength(50),
            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->maxLength(255),
            Forms\Components\TextInput::make('qs_rank')
                ->label('QS Rank')
                ->maxLength(50)
                ->hint('Hanya untuk Mitra Luar Negeri'),
            Forms\Components\Textarea::make('alamat')
                ->label('Alamat')
                ->columnSpanFull(),
            Forms\Components\Select::make('provinsi_id')
                ->label('Provinsi')
                ->relationship('provinsiModel', 'nama_provinsi')
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Set $set) => $set('kota_id', null)),
            Forms\Components\Select::make('kota_id')
                ->label('Kota')
                ->options(fn (\Filament\Schemas\Components\Utilities\Get $get): \Illuminate\Support\Collection => \App\Models\MasterKota::query()
                    ->where('provinsi_id', $get('provinsi_id'))
                    ->pluck('nama_kota', 'id'))
                ->searchable()
                ->preload(),
        ];
    }
}
