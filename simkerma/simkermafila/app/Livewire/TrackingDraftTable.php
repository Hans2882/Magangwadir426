<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
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
                        'info' => 'Menunggu TTD Direktur',
                        'success' => 'Selesai',
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
                EditAction::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-m-arrow-path')
                    ->color('primary')
                    ->modalHeading(fn (Model $record): string => 'Update Status Dokumen: ' . $record->judul)
                    ->modalWidth('2xl')
                    ->form([
                        Select::make('status_workflow')
                            ->label('Status Dokumen Saat Ini')
                            ->options([
                                'Draft' => 'Draft (Sedang Disusun)',
                                'Review Internal' => 'Review Internal',
                                'Menunggu TTD Mitra' => 'Menunggu TTD Mitra',
                                'Menunggu TTD Direktur' => 'Menunggu TTD Direktur',
                                'Selesai' => 'Selesai (Aktif)',
                            ])
                            ->required()
                            ->live(),

                        Select::make('jenis_pengajuan')
                            ->label('Jenis Pengajuan')
                            ->options([
                                'Baru' => 'Dokumen Baru',
                                'Perpanjangan' => 'Perpanjangan (Extension)',
                            ])
                            ->default('Baru'),

                        TextInput::make('nomor_dokumen')
                            ->label('Nomor Dokumen')
                            ->placeholder('Contoh: 012/SPK/POLINEMA/2026')
                            ->maxLength(255),

                        Textarea::make('feedback')
                            ->label('Catatan / Keterangan Alur')
                            ->placeholder('Catatan progres dokumen, revisi internal, dsb.')
                            ->rows(2),

                        Grid::make(2)
                            ->visible(fn ($get) => $get('status_workflow') === 'Selesai')
                            ->schema([
                                DatePicker::make('tanggal_awal')
                                    ->label('Tanggal Berlaku')
                                    ->required(fn ($get) => $get('status_workflow') === 'Selesai'),
                                DatePicker::make('tanggal_akhir')
                                    ->label('Tanggal Berakhir')
                                    ->required(fn ($get) => $get('status_workflow') === 'Selesai'),
                            ]),

                        FileUpload::make('link_dokumen')
                            ->label('Berkas Dokumen Final (PDF)')
                            ->disk('google')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(25600)
                            ->helperText('Wajib diunggah saat status diubah menjadi Selesai. Ukuran maksimal: 25 MB (format PDF).')
                            ->visible(fn ($get) => $get('status_workflow') === 'Selesai')
                            ->required(fn ($get) => $get('status_workflow') === 'Selesai')
                            ->directory(function (Model $record) {
                                $docMap = [1 => 'MoU', 2 => 'MoA', 3 => 'PKS', 4 => 'IA', 5 => 'SPK', 6 => 'LoC', 7 => 'LoI'];
                                $base = $docMap[$record->jenis_dokumen_id] ?? 'Dokumen';
                                if ($record->jenis === 'Luar Negeri' && in_array($record->jenis_dokumen_id, [1, 7])) {
                                    $base .= ' LN';
                                }
                                return $base . '/' . date('Y/m/d');
                            })
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Model $record): string {
                                $docMap = [1 => 'MoU', 2 => 'MoA', 3 => 'PKS', 4 => 'IA', 5 => 'SPK', 6 => 'LoC', 7 => 'LoI'];
                                $type = $docMap[$record->jenis_dokumen_id] ?? 'Dokumen';
                                $originalName = str_replace('&', '_', $file->getClientOriginalName());
                                return time() . "_{$type}_{$originalName}";
                            }),
                    ]),

                Action::make('edit_lengkap')
                    ->label('Edit Lengkap')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('gray')
                    ->tooltip('Buka halaman edit lengkap di tab baru untuk mengubah mitra, detail pasal, atau ekstraksi OCR')
                    ->url(function (Model $record): ?string {
                        return match ($record->jenis_dokumen_id) {
                            1, 7 => \App\Filament\Resources\MouResource::getUrl('edit', ['record' => $record]),
                            2, 3, 5, 6 => \App\Filament\Resources\PksSpkResource::getUrl('edit', ['record' => $record]),
                            4 => \App\Filament\Resources\IaResource::getUrl('edit', ['record' => $record]),
                            default => null,
                        };
                    })
                    ->openUrlInNewTab(),
            ]);
    }

    public function render()
    {
        return view('livewire.tracking-draft-table');
    }
}
