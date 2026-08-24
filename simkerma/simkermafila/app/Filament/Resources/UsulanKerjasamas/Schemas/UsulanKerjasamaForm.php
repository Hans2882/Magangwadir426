<?php

namespace App\Filament\Resources\UsulanKerjasamas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class UsulanKerjasamaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
                    
                \Filament\Schemas\Components\Section::make('Informasi Dokumen')
                    ->description('Masukkan nomor surat / dokumen Berita Acara Inisiasi')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('nomor_dokumen')
                            ->label('Nomor Dokumen')
                            ->placeholder('contoh: 123/PL2/KS/2026')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                \Filament\Schemas\Components\Section::make('Data Mitra')
                    ->description('Masukkan detail mitra yang akan diajukan')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('usulan_nama_mitra')
                            ->label('Nama Mitra')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\Select::make('usulan_negara_id')
                            ->label('Negara')
                            ->relationship('usulanNegara', 'nama_negara')
                            ->searchable()
                            ->preload()
                            ->optionsLimit(200)
                            ->hint('Kosongkan untuk Mitra Dalam Negeri (Indonesia)'),
                        \Filament\Forms\Components\Textarea::make('usulan_alamat')
                            ->label('Alamat')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),

                \Filament\Forms\Components\Select::make('kegiatans')
                    ->relationship('kegiatans', 'bidang_kerjasama')
                    ->multiple()
                    ->preload()
                    ->required()
                    ->label('Bentuk Kegiatan yang Diusulkan'),

                \Filament\Schemas\Components\Section::make('Data Pihak Pertama (Pengusul)')
                    ->description('Masukkan data pengusul yang akan dicetak pada Berita Acara Inisiasi')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('pengusul_nama')
                            ->label('Nama Pengusul')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('pengusul_nip')
                            ->label('NIP Pengusul')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('pengusul_jabatan')
                            ->label('Jabatan Pengusul')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('pengusul_jurusan')
                            ->label('Jurusan Pengusul')
                            ->placeholder('contoh: Jurusan Administrasi Niaga')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('pengusul_prodi')
                            ->label('Program Studi Pengusul')
                            ->placeholder('contoh: Program Studi D3 Administrasi Bisnis')
                            ->required()
                            ->maxLength(255),
                    ])->columns(3),
            ]);
    }
}
