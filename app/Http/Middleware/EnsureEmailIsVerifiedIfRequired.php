<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sama seperti middleware bawaan 'verified', tapi hormati toggle
 * config('eproposal.email_verification_required'). Keputusan dicek
 * saat request (bukan saat route register) supaya bisa diubah lewat
 * .env kapan saja tanpa perlu route:cache ulang, dan gampang di-test
 * per-skenario lewat config(['eproposal.email_verification_required' => ...]).
 */
class EnsureEmailIsVerifiedIfRequired
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('eproposal.email_verification_required')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail())) {
            return $request->expectsJson()
                ? abort(409, 'Email belum diverifikasi.')
                : redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
