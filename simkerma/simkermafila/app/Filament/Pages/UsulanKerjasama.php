<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Actions\Action;

class UsulanKerjasama extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.usulan-kerjasama';
    
    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Usulan Kerjasama';
    }

    public function getTitle(): string 
    {
        return 'Usulan Kerjasama';
    }

    public function table(Table $table): Table
    {
        // Using User model just to generate 2 empty rows for the mockup
        return $table
            ->query(\App\Models\User::query()->limit(2))
            ->headerActions([
                Action::make('create')
                    ->label('Ajukan Usulan Baru')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
            ])
            ->columns([
                TextColumn::make('mitra')
                    ->label('Nama Mitra')
                    ->state(fn ($record) => 'Atase Pendidikan dan Kebudayaan Manila')
                    ->description('Philippines')
                    ->icon('heroicon-o-globe-alt'),
                    
                TextColumn::make('bentuk')
                    ->label('Bentuk Kerjasama')
                    ->state(fn ($record) => 'Pertukaran Informasi, Student Exchange')
                    ->badge()
                    ->color('info')
                    ->separator(','),
                    
                TextColumn::make('pengusul')
                    ->label('Pengusul')
                    ->state(fn ($record) => 'Drs. Zubaidi, M.Pd')
                    ->description('D3 Administrasi Bisnis'),
                    
                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn ($record) => 'Menunggu Review')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-m-clock'),
            ])
            ->actions([
                Action::make('view')
                    ->label('')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
            ])
            ->paginated(false);
    }
}
