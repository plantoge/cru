<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class ResetPassword extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public bool $invalid = false;

    public function mount(string $token)
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    public function resetPassword()
    {
        $this->validate([
            'password' => 'required|min:8',
        ]);

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'token' => $this->token,
            ],
            function ($user, $password) {
                $user->password = $password; // auto-hash via cast 'hashed'
                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Password berhasil diubah. Silakan masuk.');
        }

        $this->invalid = true;
        $this->addError('password', 'Tautan reset tidak valid atau sudah kedaluwarsa. Minta link baru.');
    }

    public function render()
    {
        return view('livewire.auth.reset-password')->title('Reset Password');
    }
}
