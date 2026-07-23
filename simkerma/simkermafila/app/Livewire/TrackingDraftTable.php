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
            ]);
    }

    public function render()
    {
        return view('livewire.tracking-draft-table');
    }
}
