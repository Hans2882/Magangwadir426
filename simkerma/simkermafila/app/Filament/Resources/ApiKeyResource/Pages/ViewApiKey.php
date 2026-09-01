<?php

namespace App\Filament\Resources\ApiKeyResource\Pages;

use App\Filament\Resources\ApiKeyResource;
use Filament\Actions;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewApiKey extends ViewRecord
{
    protected static string $resource = ApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('WebAPI Information')
                    ->schema([
                        TextEntry::make('key')
                            ->label('Dapatkan JSON')
                            ->html()
                            ->formatStateUsing(function ($state) {
                                $baseUrl = config('app.url');

                                $apiUrl = "{$baseUrl}/api/v1/mitra?api_key={$state}";

                                $escapedUrl = e($apiUrl);
                                $jsUrl = json_encode($apiUrl);

                                return <<<HTML
                                    <div
                                        class="space-y-4"
                                        x-data="{ copied: false }"
                                    >
                                        <div>
                                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Endpoint WebAPI
                                            </p>

                                            <code class="block bg-gray-100 dark:bg-gray-800 p-3 rounded-lg border border-gray-300 dark:border-gray-700 text-sm overflow-auto mb-2">
                                                {$escapedUrl}
                                            </code>

                                            <div class="flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors"
                                                    @click="
                                                        navigator.clipboard.writeText({$jsUrl});
                                                        copied = true;
                                                        setTimeout(() => copied = false, 2000);
                                                    "
                                                >
                                                    <svg
                                                        class="w-4 h-4 mr-2"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                                        />
                                                    </svg>

                                                    <span>Salin Tautan</span>
                                                </button>

                                                <span
                                                    class="text-green-600 text-sm font-medium"
                                                    x-show="copied"
                                                    x-transition
                                                >
                                                    Tautan berhasil disalin
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                HTML;
                            }),

                        TextEntry::make('name')
                            ->label('Nama API Key'),

                        IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean(),

                        TextEntry::make('user.name')
                            ->label('Owner'),

                        TextEntry::make('last_used_at')
                            ->label('Last Used')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),

                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(1),

                Section::make('Informasi Pendaftaran')
                    ->schema([
                        TextEntry::make('registration_info')
                            ->label('')
                            ->html()
                            ->formatStateUsing(function () {
                                $registerUrl = route('register');

                                return <<<HTML
                                    <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                        <p>Belum Punya WebAPI Key?</p>

                                        <a
                                            href="{$registerUrl}"
                                            class="text-primary-600 hover:text-primary-700 font-medium underline"
                                        >
                                            Daftar disini
                                        </a>
                                    </div>
                                HTML;
                            }),
                    ]),
            ]);
    }
}