<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Rules\ValidCaptcha;
use App\Services\MathCaptcha;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $institusi_asal = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $captchaId = '';

    public string $captchaQuestion = '';

    public string $captchaAnswer = '';

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

    public function register()
    {
        try {
            $data = $this->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'nullable|string|max:30',
                'institusi_asal' => 'nullable|string|max:255',
                'password' => 'required|min:8|confirmed',
                'captchaAnswer' => ['required', new ValidCaptcha($this->captchaId)],
            ]);
        } catch (ValidationException $e) {
            // Token sekali pakai — reset widget biar percobaan berikutnya bisa verify ulang.
            $this->regenerateCaptcha();

            throw $e;
        }

        unset($data['captchaAnswer']);

        $user = User::create($data);
        $user->assignRole('peneliti');

        if (config('eproposal.email_verification_required')) {
            event(new Registered($user)); // trigger kirim email verifikasi (VerifyEmail notification)
        } else {
            $user->markEmailAsVerified(); // fitur dimatikan — langsung aktif, tak coba kirim email
        }

        Auth::login($user);
        session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.auth.register')->title('Daftar');
    }
}
