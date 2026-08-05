<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;
use Filament\Facades\Filament;

class FilamentLoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = auth()->user();
        
        // If they have admin panel access, send them to the admin dashboard
        if ($user && $user->canAccessPanel(Filament::getPanel('admin'))) {
            return redirect()->to(Filament::getPanel('admin')->getUrl());
        }
        
        // Otherwise, send them to the standard user dashboard
        return redirect()->to(Filament::getPanel('user')->getUrl());
    }
}
