<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MasterTemplatDokumen;

class CheckTemplat extends Command
{
    protected $signature = 'check:templat';
    protected $description = 'Check templat records';

    public function handle()
    {
        $items = MasterTemplatDokumen::all();
        foreach ($items as $item) {
            $path = public_path('templates/' . $item->file_name);
            $this->info("ID: {$item->id} | Judul: {$item->judul} | File: '{$item->file_name}' | Exists: " . (file_exists($path) ? 'YES' : 'NO') . " | Path: {$path}");
        }
    }
}
