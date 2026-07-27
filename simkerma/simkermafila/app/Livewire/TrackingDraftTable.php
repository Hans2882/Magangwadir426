<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use App\Models\Kerjasama;

class TrackingDraftTable extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Kerjasama::query()
                    ->with(['mitra', 'jenisDokumen'])
                    ->whereIn('jenis_dokumen_id', [1, 2, 3, 4, 5, 6, 7]) // All main document types
                    ->where('status_workflow', '!=', 'Selesai')
                    ->latest()
            )
            ->columns([
                TextColumn::make('mitra.nama_mitra')
                    ->label('Nama Mitra')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('jenisDokumen.nama')
                    ->label('Jenis Dokumen')
                    ->badge()
                    ->sortable(),

                TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->wrap()
                    ->limit(50),

                BadgeColumn::make('status_workflow')
                    ->label('Status Proses (Saat Ini)')
                    ->colors([
                        'secondary' => 'Draft',
                        'warning' => 'Review Internal',
                        'primary' => 'Menunggu TTD Mitra',
                    ]),

                BadgeColumn::make('jenis_pengajuan')
                    ->label('Jenis Pengajuan')
                    ->colors([
                        'success' => 'Baru',
                        'danger' => 'Perpanjangan',
                    ]),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('jenis_pengajuan')
                    ->label('Filter Jenis Pengajuan')
                    ->options([
                        'Baru' => 'Dokumen Baru',
                        'Perpanjangan' => 'Perpanjangan',
                    ]),
            ])
            ->actions([
                \Filament\Actions\Action::make('update_status')
                    ->label('Update Status & Dokumen')
                    ->icon('heroicon-m-pencil-square')
                    ->color('primary')
                    ->url(function ($record) {
                        return match ($record->jenis_dokumen_id) {
                            1 => \App\Filament\Resources\MouResource::getUrl('edit', ['record' => $record]),
                            2 => \App\Filament\Resources\MoaResource::getUrl('edit', ['record' => $record]),
                            3, 5 => \App\Filament\Resources\PksSpkResource::getUrl('edit', ['record' => $record]),
                            4 => \App\Filament\Resources\IaResource::getUrl('edit', ['record' => $record]),
                            6 => \App\Filament\Resources\LocResource::getUrl('edit', ['record' => $record]),
                            7 => \App\Filament\Resources\LoiResource::getUrl('edit', ['record' => $record]),
                            default => null,
                        };
                    })
            ]);
    }

    public function render()
    {
        return view('livewire.tracking-draft-table');
    }
}
