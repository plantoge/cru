<?php

namespace App\Livewire\Auth;

use App\Rules\ValidCaptcha;
use App\Services\MathCaptcha;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class ForgotPassword extends Component
{
    public string $email = '';

    public string $captchaId = '';

    public string $captchaQuestion = '';

    public string $captchaAnswer = '';

    public bool $sent = false;

    public function mount(MathCaptcha $captcha)
    {
        $this->regenerateCaptcha($captcha);
    }

    public function regenerateCaptcha(?MathCaptcha $captcha = null)
    {
        $soal = ($captcha ?? app(MathCaptcha::class))->generate();
        $this->captchaId = $soal['id'];
        $this->captchaQuestion = $soal['question'];
        $this->captchaAnswer = '';
    }

    public function kirimLinkReset()
    {
        try {
            $data = $this->validate([
                'email' => 'required|email',
                'captchaAnswer' => ['required', new ValidCaptcha($this->captchaId)],
            ]);
        } catch (ValidationException $e) {
            $this->regenerateCaptcha(); // token sekali pakai — soal baru buat percobaan berikutnya
            throw $e;
        }

        // Hasil $status (terdaftar/tidak/throttled) sengaja diabaikan untuk pesan ke
        // user — selalu tampil pesan generik yang sama, biar tak bisa dipakai
        // menebak email mana yang punya akun (anti user-enumeration).
        Password::sendResetLink(['email' => $data['email']]);

        $this->regenerateCaptcha();
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')->title('Lupa Password');
    }
}
