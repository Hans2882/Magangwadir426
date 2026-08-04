<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MitraResource\Pages;
use App\Filament\Resources\MitraResource\RelationManagers\KerjasamasRelationManager;
use App\Models\Mitra;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MitraResource extends Resource
{
    protected static ?string $model = Mitra::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static \UnitEnum|string|null $navigationGroup = 'Data Mitra';

    protected static ?string $navigationLabel = 'Data Mitra';

    protected static ?string $modelLabel = 'Mitra';

    protected static ?string $pluralModelLabel = 'Data Mitra';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
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
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('nama_mitra')
                    ->label('Nama Mitra')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kategori.kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('telepon')
                    ->label('No. Telepon')
                    ->default('-'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->default('-'),
                Tables\Columns\TextColumn::make('alamat')
                    ->label('Alamat')
                    ->limit(40)
                    ->default('-'),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->paginated([10, 25, 50, 100])
            ->filters([
    Tables\Filters\SelectFilter::make('kategori_id')
        ->label('Kategori IKU')
        ->relationship('kategori', 'kategori')
        ->searchable()
        ->preload(),
])
            ->defaultSort('nama_mitra', 'asc')
            ->striped();
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Detail Mitra')->columnSpan('full')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('nama_mitra')->label('Nama Mitra'),
                    \Filament\Infolists\Components\TextEntry::make('negara.nama_negara')
                        ->label('Negara')
                        ->default('Indonesia')
                        ->visible(fn ($record) => $record->negara_id >= 1),
                    \Filament\Infolists\Components\TextEntry::make('kategori.kategori')
                        ->label('Kategori IKU')
                        ->default('-')
                        ->badge()
                        ->color('primary'),
                    \Filament\Infolists\Components\TextEntry::make('telepon')->label('No. Telepon')->default('-'),
                    \Filament\Infolists\Components\TextEntry::make('email')->label('Email')->default('-'),
                    \Filament\Infolists\Components\TextEntry::make('qs_rank')->label('QS Rank')->default('-')
                        ->visible(fn ($record) => $record->negara_id >= 1),
                    \Filament\Infolists\Components\TextEntry::make('alamat')->label('Alamat')->default('-')->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            KerjasamasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMitras::route('/'),
            'create' => Pages\CreateMitra::route('/create'),
            'view'  => Pages\ViewMitra::route('/{record}'),
            'edit'  => Pages\EditMitra::route('/{record}/edit'),
        ];
    }
}
