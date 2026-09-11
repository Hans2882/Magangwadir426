<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JurnalIlmiahResource\Pages;
use App\Models\Kerjasama;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JurnalIlmiahResource extends Resource
{
    protected static ?string $model = Kerjasama::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static \UnitEnum|string|null $navigationGroup = 'Karya Tulis Ilmiah';

    protected static ?string $navigationLabel = 'Jurnal Ilmiah';

    protected static ?string $modelLabel = 'Jurnal Ilmiah';

    protected static ?string $pluralModelLabel = 'Jurnal Ilmiah';

    protected static ?string $slug = 'jurnal-ilmiah';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['mitra', 'dokumenTerkait.jenisDokumen'])
            ->where('jenis_dokumen_id', 9); // 9 = Jurnal Ilmiah
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('nama_jurnal')->label('Nama Jurnal')->required(),
            Forms\Components\TextInput::make('volume')->label('Volume')->required(),
            Forms\Components\TextInput::make('issue')->label('Issue')->required(),
            Forms\Components\DatePicker::make('tanggal_publikasi')->label('Tanggal Publikasi')->required(),
            Forms\Components\TextInput::make('link_doi')->label('Link DOI')->url()->maxLength(500),
            Forms\Components\Select::make('dokumenTerkait')
                ->label('Dokumen Terkait (MoU/PKS/IA)')
                ->relationship('dokumenTerkait', 'judul', fn (Builder $query) => $query->whereIn('jenis_dokumen_id', [1, 3, 4]))
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->jenisDokumen?->nama} - {$record->judul}")
                ->multiple()
                ->searchable()
                ->preload(),
            Forms\Components\Hidden::make('jenis_dokumen_id')->default(9),
            Forms\Components\Hidden::make('status_workflow')->default('Selesai'),
            Forms\Components\Hidden::make('jenis_pengajuan')->default('Baru'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Detail Jurnal Ilmiah')->columnSpan('full')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('nama_jurnal')->label('Nama Jurnal'),
                    \Filament\Infolists\Components\TextEntry::make('volume')->label('Volume'),
                    \Filament\Infolists\Components\TextEntry::make('issue')->label('Issue'),
                    \Filament\Infolists\Components\TextEntry::make('tanggal_publikasi')->label('Tanggal Publikasi')->date('d F Y'),
                    \Filament\Infolists\Components\TextEntry::make('link_doi')->label('Link DOI')->url(fn ($state) => $state, true)->default('-'),
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
                Tables\Columns\TextColumn::make('nama_jurnal')
                    ->label('Nama Jurnal')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->nama_jurnal),
                Tables\Columns\TextColumn::make('volume')->label('Volume'),
                Tables\Columns\TextColumn::make('issue')->label('Issue'),
                Tables\Columns\TextColumn::make('tanggal_publikasi')->label('Publikasi')->date('d M Y')->sortable(),
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
            'index' => Pages\ListJurnalIlmiahs::route('/'),
            'create' => Pages\CreateJurnalIlmiah::route('/create'),
            'view' => Pages\ViewJurnalIlmiah::route('/{record}'),
            'edit' => Pages\EditJurnalIlmiah::route('/{record}/edit'),
        ];
    }
}
