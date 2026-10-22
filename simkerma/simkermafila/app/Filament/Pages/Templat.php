<?php

namespace App\Filament\Pages;

use App\Models\MasterTemplatDokumen;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\Auth;

class Templat extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-arrow-down';

    protected string $view = 'filament.pages.templat';
    
    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Templat';
    
    protected static ?string $title = 'Download Templat Dokumen';

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if ($user?->isMahasiswa() || $user?->isDosen()) {
            return false;
        }
        return true;
    }

    public function isAdmin(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->userPrivilege?->privilege?->is_admin_panel ?? false;
    }

    protected function getHeaderActions(): array
    {
        if (! $this->isAdmin()) {
            return [];
        }

        return [
            Action::make('createTemplate')
                ->label('Tambah Templat')
                ->icon('heroicon-m-plus')
                ->modalHeading('Tambah Templat Dokumen Baru')
                ->modalWidth('lg')
                ->form([
                    Forms\Components\TextInput::make('judul')
                        ->label('Nama / Judul Templat')
                        ->placeholder('Contoh: Nota Kesepahaman (MoU) Polinema 2026')
                        ->required()
                        ->maxLength(255),
                    Grid::make(2)->schema([
                        Forms\Components\Select::make('kategori')
                            ->label('Kategori Dokumen')
                            ->options([
                                'MoU' => 'MoU (Nota Kesepahaman)',
                                'PKS' => 'PKS (Perjanjian Kerja Sama)',
                                'IA' => 'IA (Implementation Arrangement)',
                                'Laporan' => 'Laporan Pelaksanaan',
                                'Case Study' => 'Case Study',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->default('MoU')
                            ->required(),
                        Forms\Components\Select::make('bahasa')
                            ->label('Bahasa Dokumen')
                            ->options([
                                'ID' => 'Bahasa Indonesia (ID)',
                                'EN' => 'English (EN)',
                            ])
                            ->default('ID')
                            ->required(),
                    ]),
                    Forms\Components\FileUpload::make('file')
                        ->label('Berkas Templat (.docx / .doc / .pdf)')
                        ->disk('templates')
                        ->directory('')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/msword',
                            'application/pdf',
                            '.docx',
                            '.doc',
                            '.pdf',
                        ])
                        ->maxSize(20480)
                        ->required()
                        ->helperText('Ukuran maksimal berkas: 20 MB.'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Status Aktif')
                        ->helperText('Jika aktif, dokumen akan langsung tampil dan dapat diunduh.')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    MasterTemplatDokumen::create([
                        'judul' => $data['judul'],
                        'file_name' => basename($data['file']),
                        'kategori' => $data['kategori'],
                        'bahasa' => $data['bahasa'],
                        'is_active' => $data['is_active'] ?? true,
                        'urutan' => (MasterTemplatDokumen::max('urutan') ?? 0) + 1,
                    ]);

                    Notification::make()
                        ->title('Templat baru berhasil ditambahkan!')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function editTemplateAction(): Action
    {
        return Action::make('editTemplate')
            ->label('Ganti Berkas / Edit')
            ->modalHeading(function (array $arguments): string {
                $record = MasterTemplatDokumen::find($arguments['id'] ?? null);
                return 'Edit / Ganti Berkas: ' . ($record?->judul ?? 'Templat');
            })
            ->modalWidth('lg')
            ->fillForm(function (array $arguments): array {
                $record = MasterTemplatDokumen::findOrFail($arguments['id']);
                return [
                    'judul' => $record->judul,
                    'kategori' => $record->kategori,
                    'bahasa' => $record->bahasa,
                    'is_active' => $record->is_active,
                ];
            })
            ->form([
                Forms\Components\TextInput::make('judul')
                    ->label('Nama / Judul Templat')
                    ->required()
                    ->maxLength(255),
                Grid::make(2)->schema([
                    Forms\Components\Select::make('kategori')
                        ->label('Kategori Dokumen')
                        ->options([
                            'MoU' => 'MoU (Nota Kesepahaman)',
                            'PKS' => 'PKS (Perjanjian Kerja Sama)',
                            'IA' => 'IA (Implementation Arrangement)',
                            'Laporan' => 'Laporan Pelaksanaan',
                            'Case Study' => 'Case Study',
                            'Lainnya' => 'Lainnya',
                        ])
                        ->required(),
                    Forms\Components\Select::make('bahasa')
                        ->label('Bahasa Dokumen')
                        ->options([
                            'ID' => 'Bahasa Indonesia (ID)',
                            'EN' => 'English (EN)',
                        ])
                        ->required(),
                ]),
                Forms\Components\FileUpload::make('replacement_file')
                    ->label('Unggah Berkas Baru Pengganti (Opsional)')
                    ->helperText('Kosongkan jika hanya ingin mengedit informasi judul/kategori tanpa mengganti berkas file yang ada.')
                    ->disk('templates')
                    ->directory('')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/msword',
                        'application/pdf',
                        '.docx',
                        '.doc',
                        '.pdf',
                    ])
                    ->maxSize(20480),
                Forms\Components\Toggle::make('is_active')
                    ->label('Status Aktif (Tampilkan di Daftar)')
                    ->helperText('Jika dinonaktifkan, dokumen ini otomatis disembunyikan dari pengguna umum (diarsipkan).'),
            ])
            ->action(function (array $data, array $arguments): void {
                $record = MasterTemplatDokumen::findOrFail($arguments['id']);
                $record->judul = $data['judul'];
                $record->kategori = $data['kategori'];
                $record->bahasa = $data['bahasa'];
                $record->is_active = $data['is_active'];

                if (!empty($data['replacement_file'])) {
                    $oldFileName = $record->file_name;
                    $newFileName = basename($data['replacement_file']);

                    // Hapus file fisik lama jika berbeda nama dan tidak dipakai record lain
                    if ($oldFileName && $oldFileName !== $newFileName) {
                        $isUsedElsewhere = MasterTemplatDokumen::where('file_name', $oldFileName)
                            ->where('id', '!=', $record->id)
                            ->exists();

                        if (! $isUsedElsewhere) {
                            $oldPath = public_path('templates/' . $oldFileName);
                            if (file_exists($oldPath) && is_file($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                    }

                    $record->file_name = $newFileName;
                }

                $record->save();

                Notification::make()
                    ->title('Templat berhasil diperbarui!')
                    ->success()
                    ->send();
            });
    }

    public function deleteTemplateAction(): Action
    {
        return Action::make('deleteTemplate')
            ->label('Hapus Templat')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Hapus Templat Dokumen?')
            ->modalDescription('Apakah Anda yakin ingin menghapus templat ini secara permanen?')
            ->modalSubmitActionLabel('Ya, Hapus')
            ->action(function (array $arguments): void {
                $record = MasterTemplatDokumen::findOrFail($arguments['id']);
                $fileName = $record->file_name;

                // Hapus record database
                $record->delete();

                // Hapus file fisik jika tidak digunakan oleh record lain
                if ($fileName) {
                    $isUsedElsewhere = MasterTemplatDokumen::where('file_name', $fileName)->exists();
                    if (! $isUsedElsewhere) {
                        $filePath = public_path('templates/' . $fileName);
                        if (file_exists($filePath) && is_file($filePath)) {
                            @unlink($filePath);
                        }
                    }
                }

                Notification::make()
                    ->title('Templat berhasil dihapus!')
                    ->success()
                    ->send();
            });
    }

    public function toggleActive(int $id): void
    {
        if (! $this->isAdmin()) {
            return;
        }

        $record = MasterTemplatDokumen::findOrFail($id);
        $record->is_active = ! $record->is_active;
        $record->save();

        $pesan = $record->is_active 
            ? 'Templat berhasil diaktifkan kembali!' 
            : 'Templat dinonaktifkan (diarsipkan & disembunyikan dari pengguna umum).';

        Notification::make()
            ->title($pesan)
            ->color($record->is_active ? 'success' : 'warning')
            ->send();
    }

    public function getViewData(): array
    {
        $isAdmin = $this->isAdmin();
        
        $query = MasterTemplatDokumen::query()->orderBy('urutan');
        if (! $isAdmin) {
            $query->where('is_active', true);
        }

        return [
            'templates' => $query->get(),
            'isAdmin' => $isAdmin,
        ];
    }
}
