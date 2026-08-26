<?php

namespace App\Filament\Resources\MitraAwardPeriodResource\RelationManagers;

use App\Models\Mitra;
use App\Models\MitraAwardScore;
use App\Services\MitraAwardCalculator;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class ScoresRelationManager extends RelationManager
{
    protected static string $relationship = 'scores';

    protected static ?string $title = 'Peserta dan Penilaian';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Ketersediaan Dokumen')
                ->schema([
                    Forms\Components\Select::make('mitra_id')
                        ->label('Mitra')
                        ->relationship('mitra', 'nama_mitra')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->rule(function (?MitraAwardScore $record) {
                            return Rule::unique('mitra_award_scores', 'mitra_id')
                                ->where(fn ($query) => $query->where('mitra_award_period_id', $this->ownerRecord->getKey()))
                                ->ignore($record?->getKey());
                        })
                        ->live(),
                    Forms\Components\Placeholder::make('document_level')
                        ->label('Jenis dokumen terdeteksi')
                        ->content(fn (Get $get): string => $this->documentLevel($get('mitra_id'))),
                    Forms\Components\Placeholder::make('document_score_preview')
                        ->label('Skor dokumen')
                        ->content(fn (Get $get): string => (string) app(MitraAwardCalculator::class)
                            ->getDocumentScore(Mitra::find($get('mitra_id')))),
                ])->columns(3),
            Section::make('Kemitraan Akademik')->schema([
                $this->countField('kurikulum', 'Kurikulum'),
                $this->countField('magang', 'Magang'),
                $this->countField('dosen_industri', 'Dosen Industri'),
                $this->countField('rekrutmen', 'Rekrutmen'),
            ]),
            Section::make('Penelitian dan Pengabdian kepada Masyarakat')->schema([
                $this->moneyField('penelitian_cash', 'Penelitian Cash'),
                $this->moneyField('penelitian_kind', 'Penelitian In-kind'),
                $this->countField('hilirisasi', 'Hilirisasi'),
                $this->countField('khalayak_pkm', 'Khalayak PKM'),
                $this->countField('publikasi_bersama', 'Publikasi Bersama'),
                $this->countField('co_hosting', 'Co-hosting'),
            ])->columns(2),
            Section::make('Income Generation')->schema([
                $this->countField('pelatihan_sertifikasi', 'Pelatihan / Sertifikasi'),
                $this->moneyField('kajian_tenaga_ahli', 'Kajian Tenaga Ahli'),
                $this->moneyField('hibah_alat', 'Hibah Alat'),
            ])->columns(2),
            Section::make('Nilai Tambah')->schema([
                $this->countField('reputasi', 'Reputasi'),
                $this->countField('perluasan_jejaring', 'Perluasan Jejaring'),
            ]),
            Section::make('Preview Nilai')->schema([
                Forms\Components\Placeholder::make('total_score_preview')
                    ->label('Final score')
                    ->content(fn (Get $get): string => number_format($this->previewScore($get), 2, ',', '.').' / 100'),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('mitra_id')
            ->modifyQueryUsing(fn ($query) => $query->with(['mitra.kategori', 'mitra.negara']))
            ->columns([
                Tables\Columns\TextColumn::make('ranking')->label('Ranking')->badge()
                    ->color(fn (?int $state): string => match ($state) {
                        1 => 'warning',
                        2 => 'gray',
                        3 => 'orange',
                        default => 'primary',
                    })->sortable(),
                Tables\Columns\TextColumn::make('mitra.nama_mitra')->label('Nama Mitra')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('mitra.kategori.kategori')->label('Kategori IKU')->badge()->default('-'),
                Tables\Columns\TextColumn::make('mitra.negara.nama_negara')->label('Negara')->default('Indonesia'),
                Tables\Columns\TextColumn::make('dokumen_score')->label('Level Dokumen')->formatStateUsing(
                    fn (int $state): string => [0 => 'Tidak ada', 1 => 'Inisiasi / tracking', 2 => 'IA', 3 => 'PKS / SPK', 4 => 'MoU'][$state] ?? '-'
                ),
                Tables\Columns\TextColumn::make('total_score')->label('Final Score')->numeric(2)->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('participant_scope')
                    ->label('Tampilkan')
                    ->options(['all' => 'Semua peserta', 'top3' => 'Top 3', 'top10' => 'Top 10'])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? 'all') {
                            'top3' => $query->where('ranking', '<=', 3),
                            'top10' => $query->where('ranking', '<=', 10),
                            default => $query,
                        };
                    }),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah Peserta'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('ranking');
    }

    private function countField(string $name, string $label): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make($name)
            ->label($label)
            ->numeric()
            ->minValue(0)
            ->default(0)
            ->live();
    }

    private function moneyField(string $name, string $label): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make($name)
            ->label($label)
            ->numeric()
            ->prefix('Rp')
            ->minValue(0)
            ->default(0)
            ->live();
    }

    private function documentLevel(?int $mitraId): string
    {
        $score = app(MitraAwardCalculator::class)->getDocumentScore(Mitra::find($mitraId));

        return [0 => 'Tidak ada', 1 => 'Inisiasi / tracking', 2 => 'IA', 3 => 'PKS / SPK', 4 => 'MoU'][$score] ?? '-';
    }

    private function previewScore(Get $get): float
{
    $mitraId = $get('mitra_id');

    $score = new MitraAwardScore([
        'kurikulum' => $get('kurikulum') ?? 0,
        'magang' => $get('magang') ?? 0,
        'dosen_industri' => $get('dosen_industri') ?? 0,
        'rekrutmen' => $get('rekrutmen') ?? 0,
        'penelitian_cash' => $get('penelitian_cash') ?? 0,
        'penelitian_kind' => $get('penelitian_kind') ?? 0,
        'hilirisasi' => $get('hilirisasi') ?? 0,
        'khalayak_pkm' => $get('khalayak_pkm') ?? 0,
        'publikasi_bersama' => $get('publikasi_bersama') ?? 0,
        'co_hosting' => $get('co_hosting') ?? 0,
        'pelatihan_sertifikasi' => $get('pelatihan_sertifikasi') ?? 0,
        'kajian_tenaga_ahli' => $get('kajian_tenaga_ahli') ?? 0,
        'hibah_alat' => $get('hibah_alat') ?? 0,
        'reputasi' => $get('reputasi') ?? 0,
        'perluasan_jejaring' => $get('perluasan_jejaring') ?? 0,
    ]);

    $mitra = $mitraId ? Mitra::find($mitraId) : null;

    $score->dokumen_score = $mitra
        ? app(MitraAwardCalculator::class)->getDocumentScore($mitra)
        : 0;

    return app(MitraAwardCalculator::class)->calculate($score);
}
}
