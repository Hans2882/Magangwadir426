<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiKeyResource\Pages;
use App\Models\ApiKey;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ApiKeyResource extends Resource
{
    protected static ?string $model = ApiKey::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'API Keys';

    protected static ?string $modelLabel = 'API Key';

    protected static ?string $pluralModelLabel = 'API Keys';


    /*
    |--------------------------------------------------------------------------
    | FORM
    |--------------------------------------------------------------------------
    */

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->label('API Key Name')
                ->required()
                ->maxLength(255)
                ->helperText('Give your API key a memorable name'),

            Forms\Components\Toggle::make('is_active')
                ->label('Status')
                ->default(true)
                ->required(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('key')
                    ->label('API Key')
                    ->formatStateUsing(function ($state) {
                        if (blank($state)) {
                            return '-';
                        }

                        return substr($state, 0, 10)
                            . '...'
                            . substr($state, -10);
                    })
                    ->copyable()
                    ->copyableState(fn ($state) => $state)
                    ->tooltip(fn ($record) => $record->key),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])

            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])

            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])

            ->defaultSort('created_at', 'desc')
            ->striped();
    }


    /*
    |--------------------------------------------------------------------------
    | INFOLIST
    |--------------------------------------------------------------------------
    */

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('API Key Details')
                ->schema([
                    TextEntry::make('name')
                        ->label('Name'),

                    TextEntry::make('key')
                        ->label('API Key')
                        ->copyable()
                        ->copyableState(fn ($state) => $state),

                    IconEntry::make('is_active')
                        ->label('Status')
                        ->boolean(),

                    TextEntry::make('user.name')
                        ->label('Owner'),

                    TextEntry::make('last_used_at')
                        ->label('Last Used')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('-'),

                    TextEntry::make('created_at')
                        ->label('Created')
                        ->dateTime('d/m/Y H:i'),

                    TextEntry::make('updated_at')
                        ->label('Updated')
                        ->dateTime('d/m/Y H:i'),
                ])
                ->columns(2),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | PAGES
    |--------------------------------------------------------------------------
    */

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiKeys::route('/'),
            'create' => Pages\CreateApiKey::route('/create'),
            'view' => Pages\ViewApiKey::route('/{record}'),
            'edit' => Pages\EditApiKey::route('/{record}/edit'),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | QUERY
    |--------------------------------------------------------------------------
    */

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id());
    }
}