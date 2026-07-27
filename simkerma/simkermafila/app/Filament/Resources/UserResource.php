<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Admin';
    protected static ?string $modelLabel = 'Admin';
    protected static ?string $pluralModelLabel = 'Admin';
    
    protected static ?int $navigationSort = 2;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255),
                Forms\Components\Select::make('privilege_id')
                    ->label('Privilege')
                    ->options(\App\Models\Privilege::pluck('nama', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Forms\Components\Select $component, ?\App\Models\User $record) {
                        if ($record && $record->userPrivilege) {
                            $component->state($record->userPrivilege->privilege_id);
                        }
                    })
                    ->createOptionForm([
                        Forms\Components\TextInput::make('nama')->required()->maxLength(255),
                        Forms\Components\Textarea::make('deskripsi')->maxLength(65535),
                        Forms\Components\Toggle::make('can_create')->default(false),
                        Forms\Components\Toggle::make('can_read')->default(false),
                        Forms\Components\Toggle::make('can_update')->default(false),
                        Forms\Components\Toggle::make('can_delete')->default(false),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        return \App\Models\Privilege::create($data)->id;
                    })
                    ->suffixAction(
                        Action::make('editPrivilege')
                            ->icon('heroicon-m-pencil-square')
                            ->tooltip('Edit Privilege Terpilih')
                            ->form([
                                Forms\Components\TextInput::make('nama')->required()->maxLength(255),
                                Forms\Components\Textarea::make('deskripsi')->maxLength(65535),
                                Forms\Components\Toggle::make('can_create')->default(false),
                                Forms\Components\Toggle::make('can_read')->default(false),
                                Forms\Components\Toggle::make('can_update')->default(false),
                                Forms\Components\Toggle::make('can_delete')->default(false),
                            ])
                            ->fillForm(function ($get) {
                                $privId = $get('privilege_id');
                                if (! $privId) return [];
                                return \App\Models\Privilege::find($privId, ['*'])?->toArray() ?? [];
                            })
                            ->action(function (array $data, $get) {
                                $privId = $get('privilege_id');
                                if ($privId) {
                                    \App\Models\Privilege::find($privId, ['*'])?->update($data);
                                }
                            })
                            ->visible(fn ($get) => filled($get('privilege_id')))
                    ),
                Forms\Components\Select::make('program_studi_id')
                    ->label('Program Studi')
                    ->options(\App\Models\MasterProgramStudi::pluck('nama_prodi', 'id'))
                    ->searchable()
                    ->preload()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Forms\Components\Select $component, ?\App\Models\User $record) {
                        if ($record && $record->userProgramStudi) {
                            $component->state($record->userProgramStudi->program_studi_id);
                        }
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('userPrivilege.privilege.nama')
                    ->label('Privilege')
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('userProgramStudi.programStudi.nama_prodi')
                    ->label('Program Studi')
                    ->badge()
                    ->color('success')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            UserResource\Widgets\UserCountWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
