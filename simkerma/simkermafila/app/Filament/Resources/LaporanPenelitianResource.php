<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanPenelitianResource\Pages;
use App\Models\Kerjasama;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LaporanPenelitianResource extends Resource
{
    protected static ?string $model = Kerjasama::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static \UnitEnum|string|null $navigationGroup = 'Karya Tulis Ilmiah';

    protected static ?string $navigationLabel = 'Laporan Penelitian';

    protected static ?string $modelLabel = 'Laporan Penelitian';

    protected static ?string $pluralModelLabel = 'Laporan Penelitian';

    protected static ?string $slug = 'laporan-penelitian';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['dokumenTerkait.jenisDokumen'])
            ->where('jenis_dokumen_id', 11); // 11 = Laporan Penelitian
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('judul_penelitian')->label('Judul Penelitian')->required(),
            Forms\Components\TextInput::make('peneliti')->label('Peneliti')->required(),
            Forms\Components\Select::make('mitra')
                ->label('Mitra')
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => \App\Models\Mitra::query()
                    ->where('nama_mitra', 'like', "%{$search}%")
                    ->orderBy('nama_mitra')
                    ->limit(20)
                    ->pluck('nama_mitra', 'nama_mitra')
                    ->all())
                ->getOptionLabelUsing(fn ($value): ?string => $value ? \App\Models\Mitra::query()->where('nama_mitra', $value)->value('nama_mitra') : null)
                ->required(),
            Forms\Components\TextInput::make('tahun')->label('Tahun')->numeric()->minValue(1000)->maxValue(9999)->required(),
            Forms\Components\DatePicker::make('tanggal')->label('Tanggal')->required(),
            Forms\Components\Textarea::make('ringkasan')->label('Ringkasan')->rows(5)->required(),
            Forms\Components\TextInput::make('link_laporan')->label('Link Laporan')->url()->maxLength(500),
            Forms\Components\Select::make('dokumenTerkait')
                ->label('Dokumen Terkait (MoU/PKS/IA)')
                ->relationship('dokumenTerkait', 'judul', fn (Builder $query) => $query->whereIn('jenis_dokumen_id', [1, 3, 4]))
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->jenisDokumen?->nama} - {$record->judul}")
                ->multiple()
                ->searchable()
                ->preload(),
            Forms\Components\Hidden::make('jenis_dokumen_id')->default(11),
            Forms\Components\Hidden::make('status_workflow')->default('Selesai'),
            Forms\Components\Hidden::make('jenis_pengajuan')->default('Baru'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Detail Laporan Penelitian')->columnSpan('full')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('judul_penelitian')->label('Judul Penelitian')->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('peneliti')->label('Peneliti'),
                    \Filament\Infolists\Components\TextEntry::make('mitra')->label('Mitra')->getStateUsing(fn ($record) => $record->getRawOriginal('mitra')),
                    \Filament\Infolists\Components\TextEntry::make('tahun')->label('Tahun'),
                    \Filament\Infolists\Components\TextEntry::make('tanggal')->label('Tanggal')->date('d F Y'),
                    \Filament\Infolists\Components\TextEntry::make('ringkasan')->label('Ringkasan')->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('link_laporan')->label('Link Laporan')->url(fn ($state) => $state, true)->default('-'),
                ])->columns(2),
            \Filament\Schemas\Components\Section::make('Hubungan Dokumen')
                ->schema([
                    static::relatedDocumentsEntry('MoU', [1], \App\Filament\Resources\MouResource::class),
                    static::relatedDocumentsEntry('PKS / SPK', [3, 5], \App\Filament\Resources\PksSpkResource::class),
                    static::relatedDocumentsEntry('IA', [4], \App\Filament\Resources\IaResource::class),
                ]),
        ]);
    }

    protected static function relatedDocumentsEntry(string $label, array $documentTypes, string $resource): \Filament\Infolists\Components\RepeatableEntry
    {
        return \Filament\Infolists\Components\RepeatableEntry::make('dokumenTerkait')
            ->label($label)
            ->state(fn ($record) => $record->dokumenTerkait->whereIn('jenis_dokumen_id', $documentTypes)->values())
            ->contained()
            ->grid(3)
            ->schema([
                \Filament\Infolists\Components\TextEntry::make('jenisDokumen.nama')->label('Jenis'),
                \Filament\Infolists\Components\TextEntry::make('judul')
                    ->url(fn ($record) => $resource::getUrl('view', ['record' => $record]))
                    ->color('primary'),
                \Filament\Infolists\Components\TextEntry::make('status')->badge(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('judul_penelitian')
                    ->label('Judul Penelitian')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->judul_penelitian),
                Tables\Columns\TextColumn::make('peneliti')->label('Peneliti')->limit(30),
                Tables\Columns\TextColumn::make('mitra')->label('Mitra')->getStateUsing(fn ($record) => $record->getRawOriginal('mitra'))->limit(30),
                Tables\Columns\TextColumn::make('tahun')->label('Tahun')->sortable(),
                Tables\Columns\TextColumn::make('tanggal')->label('Tanggal')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('kerjasamaReferensi.judul')->label('Referensi')->default('-')->limit(30),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanPenelitians::route('/'),
            'create' => Pages\CreateLaporanPenelitian::route('/create'),
            'view' => Pages\ViewLaporanPenelitian::route('/{record}'),
            'edit' => Pages\EditLaporanPenelitian::route('/{record}/edit'),
        ];
    }
}
