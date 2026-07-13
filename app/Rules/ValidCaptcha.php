<?php

namespace App\Rules;

use App\Services\MathCaptcha;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCaptcha implements ValidationRule
{
    /** $captchaId diambil dari property Livewire sebelum rule dibentuk. */
    public function __construct(protected ?string $captchaId) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! app(MathCaptcha::class)->verify($this->captchaId, $value)) {
            $fail('Jawaban captcha salah, coba lagi.');
        }
    }
}
