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
                    
                \Filament\Forms\Components\Select::make('tipe_inisiasi')
                    ->options([
                        'Bottom-Up' => 'Bottom-Up (Inisiatif Prodi/Jurusan)',
                        'Top-Down' => 'Top-Down (Inisiatif Wadir/Direktur)',
                    ])
                    ->default('Bottom-Up')
                    ->required()
                    ->columnSpanFull(),

                \Filament\Schemas\Components\Section::make('Data Mitra Baru')
                    ->description('Masukkan detail mitra baru yang akan diajukan')
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
                        \Filament\Forms\Components\Select::make('usulan_kategori_id')
                            ->label('Kategori (IKU)')
                            ->relationship('usulanKategori', 'kategori')
                            ->searchable()
                            ->preload(),
                        \Filament\Forms\Components\TextInput::make('usulan_telepon')
                            ->label('Nomor Telepon')
                            ->maxLength(50),
                        \Filament\Forms\Components\TextInput::make('usulan_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('usulan_qs_rank')
                            ->label('QS Rank')
                            ->maxLength(50)
                            ->hint('Hanya untuk Mitra Luar Negeri'),
                        \Filament\Forms\Components\Textarea::make('usulan_alamat')
                            ->label('Alamat')
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Select::make('usulan_provinsi_id')
                            ->label('Provinsi')
                            ->relationship('usulanProvinsiModel', 'nama_provinsi')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Set $set) => $set('usulan_kota_id', null)),
                        \Filament\Forms\Components\Select::make('usulan_kota_id')
                            ->label('Kota')
                            ->options(fn (\Filament\Schemas\Components\Utilities\Get $get): \Illuminate\Support\Collection => \App\Models\MasterKota::query()
                                ->where('provinsi_id', $get('usulan_provinsi_id'))
                                ->pluck('nama_kota', 'id'))
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                \Filament\Forms\Components\Select::make('kegiatans')
                    ->relationship('kegiatans', 'bidang_kerjasama')
                    ->multiple()
                    ->preload()
                    ->required()
                    ->label('Bentuk Kegiatan yang Diusulkan'),

                \Filament\Forms\Components\FileUpload::make('dokumen_pendukung')
                    ->label('Dokumen Berita Acara Inisiasi')
                    ->helperText(new \Illuminate\Support\HtmlString('Silahkan download template Berita Acara Inisiasi <a href="/templates/berita_acara_inisiasi.docx" target="_blank" style="color: blue; text-decoration: underline;">disini</a>. Isi, tandatangani, dan upload kembali.'))
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/pdf'])
                    ->maxSize(5120)
                    ->disk('google')
                    ->directory('Usulan Inisiasi/' . date('Y/m/d'))
                    ->required(),
            ]);
    }
}
