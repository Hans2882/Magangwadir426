<?php

namespace App\Filament\Resources\UsulanKerjasamas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsulanKerjasamasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Diusulkan Oleh')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('usulan_nama_mitra')
                    ->label('Mitra Usulan Baru')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('tipe_inisiasi')
                    ->label('Tipe Inisiasi')
                    ->searchable(),
                TextColumn::make('status_usulan')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Menunggu Review' => 'warning',
                        'Disetujui' => 'success',
                        'Ditolak' => 'danger',
                        'Direvisi' => 'gray',
                        default => 'primary',
                    }),
                TextColumn::make('created_at')
                    ->label('Tanggal Usulan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\Action::make('download_dokumen')
                    ->label('Download Dokumen')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (\App\Models\UsulanKerjasama $record): string => $record->dokumen_pendukung ? asset('storage/' . $record->dokumen_pendukung) : '#')
                    ->openUrlInNewTab()
                    ->visible(fn (\App\Models\UsulanKerjasama $record): bool => (bool)$record->dokumen_pendukung),
                
                \Filament\Actions\Action::make('approve')
                    ->label('Setujui Usulan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (\App\Models\UsulanKerjasama $record) => $record->status_usulan === 'Menunggu Review')
                    ->action(function (\App\Models\UsulanKerjasama $record) {
                        // Create Official Mitra from Usulan Data
                        $newMitra = \App\Models\Mitra::create([
                            'nama_mitra' => $record->usulan_nama_mitra,
                            'kategori_id' => $record->usulan_kategori_id,
                            'negara_id' => $record->usulan_negara_id,
                            'provinsi_id' => $record->usulan_provinsi_id,
                            'kota_id' => $record->usulan_kota_id,
                            'telepon' => $record->usulan_telepon,
                            'email' => $record->usulan_email,
                            'qs_rank' => $record->usulan_qs_rank,
                            'alamat' => $record->usulan_alamat,
                        ]);

                        $record->update([
                            'status_usulan' => 'Disetujui',
                            'mitra_id' => $newMitra->id
                        ]);
                        
                        // Create Draft Kerjasama
                        \App\Models\Kerjasama::create([
                            'mitra_id' => $newMitra->id,
                            'judul' => 'Draft Kerjasama (Dari Usulan Inisiasi)',
                            'jenis' => $record->usulan_negara_id ? 'Luar Negeri' : 'Dalam Negeri',
                            'status_workflow' => 'Menunggu Dokumen',
                            'jenis_pengajuan' => 'Baru',
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Usulan Berhasil Disetujui')
                            ->body('Draft MoU telah dibuat di tabel Data Kerjasama.')
                            ->success()
                            ->send();
                    }),

                \Filament\Actions\Action::make('reject')
                    ->label('Tolak Usulan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (\App\Models\UsulanKerjasama $record) => $record->status_usulan === 'Menunggu Review')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('keterangan')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->action(function (\App\Models\UsulanKerjasama $record, array $data) {
                        $record->update([
                            'status_usulan' => 'Ditolak',
                            'keterangan' => $data['keterangan']
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Usulan Ditolak')
                            ->success()
                            ->send();
                    }),
                    
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
