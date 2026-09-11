<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KaryaRujukanResource\Pages;
use App\Models\Kerjasama;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class KaryaRujukanResource extends Resource
{
    protected static ?string $model = Kerjasama::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-book-open';

    protected static \UnitEnum|string|null $navigationGroup = 'Karya Tulis Ilmiah';

    protected static ?string $navigationLabel = 'Karya Rujukan';

    protected static ?string $modelLabel = 'Karya Rujukan';

    protected static ?string $pluralModelLabel = 'Karya Rujukan';

    protected static ?string $slug = 'karya-rujukan';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['mitra', 'dokumenTerkait.jenisDokumen'])
            ->where('jenis_dokumen_id', 10); // 10 = Karya Rujukan
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('judul_karya')->label('Judul Karya')->required(),
            Forms\Components\TextInput::make('penulis')->label('Penulis')->required(),
            Forms\Components\TextInput::make('jenis_karya')->label('Jenis Karya')->required(),
            Forms\Components\TextInput::make('penerbit')->label('Penerbit')->required(),
            Forms\Components\TextInput::make('isbn_issn')->label('ISBN/ISSN'),
            Forms\Components\TextInput::make('tahun_terbit')->label('Tahun Terbit')->numeric()->minValue(1000)->maxValue(9999)->required(),
            Forms\Components\TextInput::make('link_dokumen')->label('Link Dokumen')->url()->maxLength(500),
            Forms\Components\Select::make('dokumenTerkait')
                ->label('Dokumen Terkait (MoU/PKS/IA)')
                ->relationship('dokumenTerkait', 'judul', fn (Builder $query) => $query->whereIn('jenis_dokumen_id', [1, 3, 4]))
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->jenisDokumen?->nama} - {$record->judul}")
                ->multiple()
                ->searchable()
                ->preload(),
            Forms\Components\Hidden::make('jenis_dokumen_id')->default(10),
            Forms\Components\Hidden::make('status_workflow')->default('Selesai'),
            Forms\Components\Hidden::make('jenis_pengajuan')->default('Baru'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Detail Karya Rujukan')->columnSpan('full')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('judul_karya')->label('Judul Karya')->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('penulis')->label('Penulis'),
                    \Filament\Infolists\Components\TextEntry::make('jenis_karya')->label('Jenis Karya'),
                    \Filament\Infolists\Components\TextEntry::make('penerbit')->label('Penerbit'),
                    \Filament\Infolists\Components\TextEntry::make('isbn_issn')->label('ISBN/ISSN')->default('-'),
                    \Filament\Infolists\Components\TextEntry::make('tahun_terbit')->label('Tahun Terbit'),
                    \Filament\Infolists\Components\TextEntry::make('link_dokumen')->label('Link Dokumen')->url(fn ($state) => $state, true)->default('-'),
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
                Tables\Columns\TextColumn::make('judul_karya')
                    ->label('Judul Karya')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->judul_karya),
                Tables\Columns\TextColumn::make('penulis')->label('Penulis')->limit(30),
                Tables\Columns\TextColumn::make('jenis_karya')->label('Jenis Karya'),
                Tables\Columns\TextColumn::make('tahun_terbit')->label('Tahun')->sortable(),
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
            'index' => Pages\ListKaryaRujukans::route('/'),
            'create' => Pages\CreateKaryaRujukan::route('/create'),
            'view' => Pages\ViewKaryaRujukan::route('/{record}'),
            'edit' => Pages\EditKaryaRujukan::route('/{record}/edit'),
        ];
    }
}
