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
        return $table
            ->query(\App\Models\User::query()->where('id', -1))
            ->headerActions([
                Action::make('create')
                    ->label('Ajukan Usulan Baru')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
            ])
            ->columns([
                TextColumn::make('nama_mitra')
                    ->label('Nama Mitra')
                    ->icon('heroicon-o-globe-alt'),
                    
                TextColumn::make('bentuk_kerjasama')
                    ->label('Bentuk Kerjasama')
                    ->badge()
                    ->color('info'),
                    
                TextColumn::make('pengusul')
                    ->label('Pengusul'),
                    
                TextColumn::make('status')
                    ->label('Status')
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
