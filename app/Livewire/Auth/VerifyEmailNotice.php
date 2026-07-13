<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class VerifyEmailNotice extends Component
{
    public bool $terkirim = false;

    public function kirimUlang()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectRoute('dashboard');

            return;
        }

        Auth::user()->sendEmailVerificationNotification();
        $this->terkirim = true;
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    }

    public function render()
    {
        return view('livewire.auth.verify-email-notice')->title('Verifikasi Email');
    }
}
