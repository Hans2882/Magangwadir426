<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MitraAwardPeriodResource\Pages;
use App\Filament\Resources\MitraAwardPeriodResource\RelationManagers\ScoresRelationManager;
use App\Models\MitraAwardPeriod;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MitraAwardPeriodResource extends Resource
{
    protected static ?string $model = MitraAwardPeriod::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-trophy';

    protected static \UnitEnum|string|null $navigationGroup = 'Mitra Awards';

    protected static ?string $navigationLabel = 'Mitra Awards';

    protected static ?string $modelLabel = 'Periode Mitra Awards';

    protected static ?string $pluralModelLabel = 'Mitra Awards';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('nama')
                ->label('Nama Periode')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('tahun')
                ->label('Tahun')
                ->numeric()
                ->minValue(2000)
                ->maxValue(9999)
                ->required(),
            Forms\Components\DatePicker::make('tanggal_mulai')->label('Tanggal Mulai'),
            Forms\Components\DatePicker::make('tanggal_selesai')->label('Tanggal Selesai'),
            Forms\Components\Toggle::make('is_active')
                ->label('Periode Aktif')
                ->helperText('Mengaktifkan periode ini akan menonaktifkan periode lainnya.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tahun')->label('Tahun')->sortable(),
                Tables\Columns\TextColumn::make('scores_count')->label('Peserta')->counts('scores')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
                Tables\Columns\TextColumn::make('tanggal_mulai')->label('Mulai')->date('d M Y'),
                Tables\Columns\TextColumn::make('tanggal_selesai')->label('Selesai')->date('d M Y'),
            ])
            ->recordUrl(fn (MitraAwardPeriod $record): string => static::getUrl('view', ['record' => $record]))
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('tahun', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Pemenang')
                ->schema([
                    RepeatableEntry::make('winners')
                        ->label('Top 3 Mitra Awards')
                        ->state(fn (MitraAwardPeriod $record): array => $record->scores()
                            ->with(['mitra.kategori', 'mitra.negara'])
                            ->whereNotNull('ranking')
                            ->orderBy('ranking')
                            ->limit(3)
                            ->get()
                            ->map(fn ($score): array => [
                                'ranking' => $score->ranking,
                                'nama_mitra' => $score->mitra?->nama_mitra ?? '-',
                                'total_score' => number_format($score->total_score, 2, ',', '.'),
                                'kategori' => $score->mitra?->kategori?->kategori ?? '-',
                                'negara' => $score->mitra?->negara?->nama_negara ?? 'Indonesia',
                            ])->all())
                        ->schema([
                            TextEntry::make('ranking')->label('Peringkat')->badge(),
                            TextEntry::make('nama_mitra')->label('Mitra'),
                            TextEntry::make('total_score')->label('Final Score'),
                            TextEntry::make('kategori')->label('Kategori'),
                            TextEntry::make('negara')->label('Negara'),
                        ])->columns(5),
                ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('scores');
    }

    public static function getRelations(): array
    {
        return [ScoresRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMitraAwardPeriods::route('/'),
            'create' => Pages\CreateMitraAwardPeriod::route('/create'),
            'view' => Pages\ViewMitraAwardPeriod::route('/{record}'),
            'edit' => Pages\EditMitraAwardPeriod::route('/{record}/edit'),
        ];
    }
}
