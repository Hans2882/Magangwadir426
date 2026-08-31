<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KuisionerKepuasanResource\Pages;
use App\Filament\Resources\KuisionerKepuasanResource\RelationManagers\FollowupRelationManager;
use App\Models\KuisionerKepuasan;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class KuisionerKepuasanResource extends Resource
{
    protected static ?string $model = KuisionerKepuasan::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static \UnitEnum|string|null $navigationGroup = 'Data Mitra';

    protected static ?string $navigationLabel = 'Cek Isi Kuisioner';

    protected static ?string $modelLabel = 'Kuisioner Mitra';

    protected static ?string $pluralModelLabel = 'Cek Isi Kuisioner';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Data Responden')->schema([
                Forms\Components\TextInput::make('nama')->label('Nama')->required(),
                Forms\Components\TextInput::make('jabatan')->label('Jabatan')->required(),
                Forms\Components\TextInput::make('instansi')->label('Instansi')->required(),
                Forms\Components\TextInput::make('email')->label('Email')->email()->required(),
                Forms\Components\TextInput::make('telepon')->label('Telepon')->required(),
                Forms\Components\TextInput::make('nomor_dokumen')->label('Nomor Dokumen')->required(),
            ])->columns(2),

            \Filament\Schemas\Components\Section::make('Penilaian')->schema([
                Forms\Components\TextInput::make('komunikasi')->label('Komunikasi')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('proses')->label('Proses')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('bantuan')->label('Bantuan')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('sdm_profesionalisme')->label('SDM Profesionalisme')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('harapan')->label('Harapan')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('manfaat')->label('Manfaat')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('kembali')->label('Kembali')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('implementasi')->label('Implementasi')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('laporan')->label('Laporan')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('etika')->label('Etika')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('kepemimpinan')->label('Kepemimpinan')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('etos_kerja')->label('Etos Kerja')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('komunikasi_alumni')->label('Komunikasi Alumni')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('kerjasama_tim')->label('Kerjasama Tim')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('keahlian_bidang_ilmu')->label('Keahlian Bidang Ilmu')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('keahlian_bidang_ilmu_terapan')->label('Keahlian Bidang Ilmu Terapan')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('bahasa_asing')->label('Bahasa Asing')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('teknologi_informasi')->label('Teknologi Informasi')->numeric()->minValue(1)->maxValue(5),
                Forms\Components\TextInput::make('pengembangan_diri')->label('Pengembangan Diri')->numeric()->minValue(1)->maxValue(5),
            ])->columns(3),

            \Filament\Schemas\Components\Section::make('Catatan')->schema([
                Forms\Components\Textarea::make('saran_kerjasama')->label('Saran Kerjasama')->rows(3),
                Forms\Components\Textarea::make('saran_alumni')->label('Saran Alumni')->rows(3),
                Forms\Components\TextInput::make('program_studi_alumni')->label('Program Studi Alumni')->nullable(),
                Forms\Components\Select::make('alumni_ada')->label('Apakah Alumni Ada?')->options(['Ya' => 'Ya', 'Tidak' => 'Tidak'])->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Tanggal Input')->dateTime('d M Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('instansi')->label('Instansi')->searchable(),
                Tables\Columns\TextColumn::make('nomor_dokumen')->label('Nomor Dokumen')->searchable(),
                Tables\Columns\TextColumn::make('followups_count')
                    ->label('Tindak Lanjut')
                    ->counts('followups')
                    ->badge()
                    ->color('primary')
                    ->default(0),
                Tables\Columns\TextColumn::make('latest_followup_status')
                    ->label('Status Tindak Lanjut')
                    ->state(function (KuisionerKepuasan $record): string {
                        $latest = $record->followups()->latest()->first();
                        if (!$latest) {
                            return 'belum ada';
                        }
                        return $latest->status;
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'warning',
                        'close' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Open',
                        'close' => 'Close',
                        default => 'Belum Ada',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Data Responden')->schema([
                Infolists\Components\TextEntry::make('nama')->label('Nama'),
                Infolists\Components\TextEntry::make('jabatan')->label('Jabatan'),
                Infolists\Components\TextEntry::make('instansi')->label('Instansi'),
                Infolists\Components\TextEntry::make('email')->label('Email'),
                Infolists\Components\TextEntry::make('telepon')->label('Telepon'),
                Infolists\Components\TextEntry::make('nomor_dokumen')->label('Nomor Dokumen'),
            ])->columns(2),

            \Filament\Schemas\Components\Section::make('Penilaian')->schema([
                Infolists\Components\TextEntry::make('komunikasi')->label('Komunikasi'),
                Infolists\Components\TextEntry::make('proses')->label('Proses'),
                Infolists\Components\TextEntry::make('bantuan')->label('Bantuan'),
                Infolists\Components\TextEntry::make('sdm_profesionalisme')->label('SDM Profesionalisme'),
                Infolists\Components\TextEntry::make('harapan')->label('Harapan'),
                Infolists\Components\TextEntry::make('manfaat')->label('Manfaat'),
                Infolists\Components\TextEntry::make('kembali')->label('Kembali'),
                Infolists\Components\TextEntry::make('implementasi')->label('Implementasi'),
                Infolists\Components\TextEntry::make('laporan')->label('Laporan'),
                Infolists\Components\TextEntry::make('etika')->label('Etika'),
                Infolists\Components\TextEntry::make('kepemimpinan')->label('Kepemimpinan'),
                Infolists\Components\TextEntry::make('etos_kerja')->label('Etos Kerja'),
                Infolists\Components\TextEntry::make('komunikasi_alumni')->label('Komunikasi Alumni'),
                Infolists\Components\TextEntry::make('kerjasama_tim')->label('Kerjasama Tim'),
                Infolists\Components\TextEntry::make('keahlian_bidang_ilmu')->label('Keahlian Bidang Ilmu'),
                Infolists\Components\TextEntry::make('keahlian_bidang_ilmu_terapan')->label('Keahlian Bidang Ilmu Terapan'),
                Infolists\Components\TextEntry::make('bahasa_asing')->label('Bahasa Asing'),
                Infolists\Components\TextEntry::make('teknologi_informasi')->label('Teknologi Informasi'),
                Infolists\Components\TextEntry::make('pengembangan_diri')->label('Pengembangan Diri'),
            ])->columns(3),

            \Filament\Schemas\Components\Section::make('Catatan')->schema([
                Infolists\Components\TextEntry::make('saran_kerjasama')->label('Saran Kerjasama'),
                Infolists\Components\TextEntry::make('saran_alumni')->label('Saran Alumni'),
                Infolists\Components\TextEntry::make('program_studi_alumni')->label('Program Studi Alumni'),
                Infolists\Components\TextEntry::make('alumni_ada')->label('Apakah Alumni Ada?'),
            ])->columns(2),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            FollowupRelationManager::class,
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKuisionerKepuasans::route('/'),
            'view' => Pages\ViewKuisionerKepuasan::route('/{record}'),
            'edit' => Pages\EditKuisionerKepuasan::route('/{record}/edit'),
        ];
    }
}
