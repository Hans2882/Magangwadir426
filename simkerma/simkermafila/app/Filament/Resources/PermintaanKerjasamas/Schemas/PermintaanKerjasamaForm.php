<?php

namespace App\Filament\Resources\PermintaanKerjasamas\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class PermintaanKerjasamaForm
{
    public static function configure(Schema $schema)
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Alasan Penolakan')
                ->schema([
                    \Filament\Forms\Components\Placeholder::make('keterangan_tolak')
                        ->label('')
                        ->content(fn ($record) => $record ? $record->keterangan_tolak : '-'),
                ])
                ->visible(fn ($record) => $record && $record->status_permintaan === 'Ditolak'),

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
                ->preload()
                ->required(),
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
                ->afterStateUpdated(fn ($set) => $set('kota_id', null)),
            Forms\Components\Select::make('kota_id')
                ->label('Kota')
                ->options(fn ($get): \Illuminate\Support\Collection => \App\Models\MasterKota::query()
                    ->where('provinsi_id', $get('provinsi_id'))
                    ->pluck('nama_kota', 'id'))
                ->searchable()
                ->preload(),
                
            // Workflow hidden fields
            Forms\Components\Hidden::make('status_permintaan')
                ->default('Proses'),
            Forms\Components\Hidden::make('user_id')
                ->default(fn () => \Illuminate\Support\Facades\Auth::id()),
        ]);
    }
}
