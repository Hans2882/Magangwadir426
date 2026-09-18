<?php

namespace App\Filament\Resources\PermintaanKerjasamas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\Textarea;

class PermintaanKerjasamasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_mitra')
                    ->label('Nama Mitra')
                    ->searchable()
                    ->sortable()
                    ->description(function ($record) {
                        $words = array_slice(explode(' ', $record->nama_mitra), 0, 3);
                        $fuzzyPattern = '%' . implode('%', $words) . '%';
                        return \App\Models\Mitra::query()->where('nama_mitra', 'like', $fuzzyPattern)->exists() 
                            ? '⚠️ Potensi Duplikat di Data Mitra' 
                            : null;
                    })
                    ->color(function ($record) {
                        $words = array_slice(explode(' ', $record->nama_mitra), 0, 3);
                        $fuzzyPattern = '%' . implode('%', $words) . '%';
                        return \App\Models\Mitra::query()->where('nama_mitra', 'like', $fuzzyPattern)->exists() 
                            ? 'danger' 
                            : null;
                    }),
                TextColumn::make('kategori.kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nama_pengusul_display')
                    ->label('Pengusul')
                    ->getStateUsing(fn ($record) => $record->pengusul ? $record->pengusul->name : ($record->nama_pengusul ?? '-'))
                    ->searchable(['nama_pengusul', 'pengusul.name'])
                    ->sortable(),
                TextColumn::make('status_permintaan')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Proses' => 'warning',
                        'Disetujui' => 'success',
                        'Ditolak' => 'danger',
                        default => 'primary',
                    })
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Diajukan Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Permintaan Mitra')
                    ->modalDescription('Aksi ini akan memindahkan data mitra ke tabel Mitra resmi.')
                    ->action(function ($record) {
                        \App\Models\Mitra::create([
                            'nama_mitra' => $record->nama_mitra,
                            'kategori_id' => $record->kategori_id,
                            'negara_id' => $record->negara_id,
                            'qs_rank' => $record->qs_rank,
                            'telepon' => $record->telepon,
                            'email' => $record->email,
                            'alamat' => $record->alamat,
                            'kota' => $record->kota,
                            'provinsi' => $record->provinsi,
                            'provinsi_id' => $record->provinsi_id,
                            'kota_id' => $record->kota_id,
                        ]);
                        
                        $record->update(['status_permintaan' => 'Disetujui']);
                        \Filament\Notifications\Notification::make()->title('Mitra Resmi Ditambahkan')->success()->send();
                    })
                    ->visible(fn ($record) => $record->status_permintaan === 'Proses'),
                    
                Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('keterangan_tolak')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status_permintaan' => 'Ditolak',
                            'keterangan_tolak' => $data['keterangan_tolak']
                        ]);
                        \Filament\Notifications\Notification::make()->title('Permintaan Ditolak')->success()->send();
                    })
                    ->visible(fn ($record) => $record->status_permintaan === 'Proses'),
                    
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
