<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Captcha soal hitung sederhana, self-hosted — tanpa provider luar,
 * tanpa panggilan API, tanpa dependency ekstensi PHP tambahan (GD dst).
 * Jawaban benar disimpan di session (server-side only), tidak pernah
 * dikirim ke client. Sekali pakai — session key dihapus setelah verify.
 */
class MathCaptcha
{
    protected const PREFIX = 'captcha_';

    /** Bikin soal baru. @return array{id:string,question:string} */
    public function generate(): array
    {
        $a = random_int(1, 15);
        $b = random_int(1, 15);
        $tambah = (bool) random_int(0, 1);

        if (! $tambah && $a < $b) {
            [$a, $b] = [$b, $a]; // hindari hasil pengurangan negatif
        }

        $jawaban = $tambah ? $a + $b : $a - $b;
        $id = (string) Str::uuid();

        session()->put(self::PREFIX.$id, $jawaban);

        return ['id' => $id, 'question' => "{$a} ".($tambah ? '+' : '-')." {$b}"];
    }

    /** Cocokkan jawaban user; session key selalu dihapus (sekali pakai). */
    public function verify(?string $id, mixed $jawabanUser): bool
    {
        if (! $id || $jawabanUser === null || $jawabanUser === '') {
            return false;
        }

        $key = self::PREFIX.$id;
        $expected = session($key);
        session()->forget($key);

        return $expected !== null && is_numeric($jawabanUser) && (int) $jawabanUser === (int) $expected;
    }
}
