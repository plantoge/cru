<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Klik link signed dari email verifikasi.
     *
     * Tidak bergantung pada sesi login: user diambil dari {id} di URL, lalu
     * hash email dicek manual. Middleware 'signed' sudah menjamin URL tak
     * dipalsu — jadi link bisa diklik dari device mana pun (mis. HP) tanpa
     * harus login dulu.
     */
    public function __invoke(Request $request, string $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Tautan verifikasi tidak sah.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified(); // isi kolom email_verified_at
            event(new Verified($user));
        }

        Auth::login($user); // langsung login setelah verifikasi

        return redirect()->route('dashboard')->with('status', 'Email berhasil diverifikasi.');
    }
}
