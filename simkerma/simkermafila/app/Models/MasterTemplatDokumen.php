<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterTemplatDokumen extends Model
{
    use HasFactory;

    protected $table = 'master_templat_dokumens';

    protected $fillable = [
        'judul',
        'file_name',
        'kategori',
        'bahasa',
        'is_active',
        'urutan',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    public function getUrlAttribute(): string
    {
        return asset('templates/' . $this->file_name);
    }

    public function getFileSizeFormattedAttribute(): ?string
    {
        $path = public_path('templates/' . $this->file_name);
        if (!file_exists($path)) {
            return null;
        }

        $bytes = filesize($path);
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }
        return $bytes . ' B';
    }
}
