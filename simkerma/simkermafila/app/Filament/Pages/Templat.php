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
                        ->visibility('public')
                        ->preserveFilenames()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/msword',
                            'application/pdf',
                        ])
                        ->maxSize(51200)
                        ->required()
                        ->helperText('Ukuran maksimal berkas: 50 MB.')
                        ->saveUploadedFileUsing(function (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file): ?string {
                            $fileName = $file->getClientOriginalName();
                            $target = public_path('templates/' . $fileName);
                            $source = $file->getRealPath();

                            if (file_exists($source) && is_file($source)) {
                                @copy($source, $target);
                            }

                            if (! file_exists($target) || filesize($target) === 0) {
                                $stream = fopen($source, 'r');
                                if ($stream) {
                                    \Illuminate\Support\Facades\Storage::disk('templates')->put($fileName, $stream, ['visibility' => 'public']);
                                    fclose($stream);
                                }
                            }

                            return (file_exists($target) && filesize($target) > 0) ? $fileName : null;
                        }),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Status Aktif')
                        ->helperText('Jika aktif, dokumen akan langsung tampil dan dapat diunduh.')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $targetDir = public_path('templates');
                    if (! file_exists($targetDir)) {
                        @mkdir($targetDir, 0775, true);
                    }

                    if (! is_writable($targetDir)) {
                        \Illuminate\Support\Facades\Log::error("Template Upload Error: Direktori [{$targetDir}] tidak dapat ditulis oleh web server (Permission Denied).");

                        Notification::make()
                            ->title('Izin Penyimpanan Server Ditolak')
                            ->body('Folder penyimpanan templat di server (public/templates) tidak memiliki izin tulis untuk web server (permission denied). Hubungi admin server untuk mengatur izin folder.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $fileName = $this->storeUploadedTemplateFile($data['file'] ?? null);

                    if (! $fileName) {
                        Notification::make()
                            ->title('Gagal mengunggah berkas')
                            ->body('Berkas fisik gagal tersimpan ke server. Pastikan Anda menunggu hingga proses unggah selesai (indikator hijau) sebelum menekan tombol Kirim, dan coba kembali.')
                            ->danger()
                            ->send();

                        return;
                    }

                    MasterTemplatDokumen::create([
                        'judul' => $data['judul'],
                        'file_name' => $fileName,
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
                    ->helperText('Kosongkan jika hanya ingin mengedit informasi judul/kategori tanpa mengganti berkas file yang ada. Ukuran maksimal: 50 MB.')
                    ->disk('templates')
                    ->directory('')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/msword',
                        'application/pdf',
                    ])
                    ->maxSize(51200)
                    ->saveUploadedFileUsing(function (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file): ?string {
                        $fileName = $file->getClientOriginalName();
                        $target = public_path('templates/' . $fileName);
                        $source = $file->getRealPath();

                        if (file_exists($source) && is_file($source)) {
                            @copy($source, $target);
                        }

                        if (! file_exists($target) || filesize($target) === 0) {
                            $stream = fopen($source, 'r');
                            if ($stream) {
                                \Illuminate\Support\Facades\Storage::disk('templates')->put($fileName, $stream, ['visibility' => 'public']);
                                fclose($stream);
                            }
                        }

                        return (file_exists($target) && filesize($target) > 0) ? $fileName : null;
                    }),
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
                    $targetDir = public_path('templates');
                    if (! file_exists($targetDir)) {
                        @mkdir($targetDir, 0775, true);
                    }

                    if (! is_writable($targetDir)) {
                        \Illuminate\Support\Facades\Log::error("Template Upload Error: Direktori [{$targetDir}] tidak dapat ditulis oleh web server (Permission Denied).");

                        Notification::make()
                            ->title('Izin Penyimpanan Server Ditolak')
                            ->body('Folder penyimpanan templat di server (public/templates) tidak memiliki izin tulis untuk web server (permission denied). Hubungi admin server untuk mengatur izin folder.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $newFileName = $this->storeUploadedTemplateFile($data['replacement_file']);

                    if (! $newFileName) {
                        Notification::make()
                            ->title('Gagal mengunggah berkas pengganti')
                            ->body('Berkas fisik baru gagal tersimpan ke server. Data templat belum diubah. Silakan pastikan proses unggah selesai lalu coba kembali.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $oldFileName = $record->file_name;

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

    /**
     * Memastikan berkas templat fisik benar-benar tersimpan ke disk 'templates' (public/templates/)
     * dan mengembalikan nama berkasnya. Mengembalikan null jika gagal.
     */
    protected function storeUploadedTemplateFile(mixed $fileInput): ?string
    {
        if (is_array($fileInput)) {
            $fileInput = \Illuminate\Support\Arr::first($fileInput);
        }

        if (blank($fileInput)) {
            \Illuminate\Support\Facades\Log::warning('Template Upload Warning: Input berkas kosong.');
            return null;
        }

        $targetDir = public_path('templates');
        if (! file_exists($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }

        if (! is_writable($targetDir)) {
            \Illuminate\Support\Facades\Log::error("Template Upload Error: Direktori target [{$targetDir}] tidak memiliki izin tulis (Permission Denied).");
            return null;
        }

        $templatesDisk = \Illuminate\Support\Facades\Storage::disk('templates');
        $fileName = null;

        // 1. Jika bertipe TemporaryUploadedFile (Livewire)
        if ($fileInput instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            $fileName = $fileInput->getClientOriginalName();
            $fileInput->storeAs('', $fileName, ['disk' => 'templates', 'visibility' => 'public']);
            if ($templatesDisk->exists($fileName)) {
                return $fileName;
            }
        }

        // 2. Jika bertipe UploadedFile (Laravel Standard)
        if ($fileInput instanceof \Illuminate\Http\UploadedFile) {
            $fileName = $fileInput->getClientOriginalName();
            $fileInput->storeAs('', $fileName, ['disk' => 'templates', 'visibility' => 'public']);
            if ($templatesDisk->exists($fileName)) {
                return $fileName;
            }
        }

        // 3. Jika bertipe string
        if (is_string($fileInput)) {
            $fileName = basename($fileInput);

            // Cek apakah berkas sudah otomatis tersimpan di disk 'templates'
            if ($templatesDisk->exists($fileName)) {
                return $fileName;
            }

            // Jika belum ada di disk 'templates', cari di disk temporary Livewire
            $lwDiskName = \Livewire\Features\SupportFileUploads\FileUploadConfiguration::disk();
            $lwDisk = \Illuminate\Support\Facades\Storage::disk($lwDiskName);

            // Kemungkinan 1: Berkas di livewire-tmp dengan nama file/hash yang sama
            $candidatePaths = [
                $fileInput,
                'livewire-tmp/' . $fileName,
                'livewire-tmp/' . $fileInput,
                $fileName,
            ];

            foreach ($candidatePaths as $candidate) {
                if ($lwDisk->exists($candidate)) {
                    $stream = $lwDisk->readStream($candidate);
                    if ($stream) {
                        $templatesDisk->put($fileName, $stream, ['visibility' => 'public']);
                        if (is_resource($stream)) {
                            fclose($stream);
                        }
                        if ($templatesDisk->exists($fileName)) {
                            return $fileName;
                        }
                    }
                }
            }

            // Kemungkinan 2: $fileName adalah original name, sedangkan berkas fisik di livewire-tmp tersimpan sebagai hash
            // Cari metadata JSON di livewire-tmp yang memiliki "name" sama dengan $fileName
            $allFiles = $lwDisk->files('livewire-tmp');
            foreach ($allFiles as $metaFile) {
                if (str_ends_with($metaFile, '.json')) {
                    $meta = json_decode($lwDisk->get($metaFile), true);
                    if (($meta['name'] ?? null) === $fileName) {
                        $hashFile = substr($metaFile, 0, -5); // Hapus akhiran .json
                        if ($lwDisk->exists($hashFile)) {
                            $stream = $lwDisk->readStream($hashFile);
                            if ($stream) {
                                $templatesDisk->put($fileName, $stream, ['visibility' => 'public']);
                                if (is_resource($stream)) {
                                    fclose($stream);
                                }
                                if ($templatesDisk->exists($fileName)) {
                                    return $fileName;
                                }
                            }
                        }
                    }
                }
            }

            // Kemungkinan 3: Cek path lokal absolut
            if (file_exists($fileInput) && is_file($fileInput)) {
                $stream = fopen($fileInput, 'r');
                if ($stream) {
                    $templatesDisk->put($fileName, $stream, ['visibility' => 'public']);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                    if ($templatesDisk->exists($fileName)) {
                        return $fileName;
                    }
                }
            }
        }

        $fileExists = ($fileName && $templatesDisk->exists($fileName));
        if (! $fileExists) {
            \Illuminate\Support\Facades\Log::warning('Template Upload Warning: Berkas fisik tidak ditemukan di disk templates.', [
                'input' => is_scalar($fileInput) ? $fileInput : gettype($fileInput),
                'resolved_filename' => $fileName,
                'target_dir' => $targetDir,
                'target_dir_writable' => is_writable($targetDir),
            ]);
            return null;
        }

        return $fileName;
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
