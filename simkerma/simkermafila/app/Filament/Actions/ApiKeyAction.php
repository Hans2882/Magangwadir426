<?php

namespace App\Filament\Actions;

use App\Models\ApiKey;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ApiKeyAction extends Action
{
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'apiDocumentation')
            ->label('API')
            ->icon('heroicon-o-key')
            ->color('info')
            ->modalHeading('WebAPI Endpoint')
            ->modalWidth('lg')
            ->action(function (Action $action) {
                $apiKey = ApiKey::where('user_id', Auth::id())
                    ->active()
                    ->first();

                if (!$apiKey) {
                    Notification::make()
                        ->warning()
                        ->title('Belum Ada WebAPI Key')
                        ->body('Silakan buat API Key terlebih dahulu di halaman API Keys settings.')
                        ->send();
                    return;
                }

                $baseUrl = config('app.url');
                $apiUrl = "{$baseUrl}/api/v1/mitra?api_key={$apiKey->key}";

                Notification::make()
                    ->success()
                    ->title('Endpoint Disalin')
                    ->body('Endpoint WebAPI telah disalin ke clipboard.')
                    ->persistent()
                    ->action(
                        fn () => null
                    )
                    ->send();
            })
            ->modalContent(function () {
                $apiKey = ApiKey::where('user_id', Auth::id())
                    ->active()
                    ->first();

                if (!$apiKey) {
                    $registerUrl = route('register');
                    return view('components.api-key-unavailable', ['registerUrl' => $registerUrl]);
                }

                $baseUrl = config('app.url');
                $apiUrl = "{$baseUrl}/api/v1/mitra?api_key={$apiKey->key}";

                return view('components.api-key-modal', ['apiUrl' => $apiUrl]);
            })
            ->slideOver();
    }
}