<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateApiKey extends Command
{
    protected $signature = 'api-key:generate {name}';

    protected $description = 'Generate API Key';

    public function handle(): int
    {
        $key = 'sk_live_' . Str::random(40);

        $apiKey = ApiKey::create([
            'name' => $this->argument('name'),
            'key' => $key,
        ]);

        $this->info('API Key berhasil dibuat:');
        $this->line($apiKey->key);

        return self::SUCCESS;
    }
}