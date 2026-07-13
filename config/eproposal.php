<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Verifikasi Email Registrasi
    |--------------------------------------------------------------------------
    |
    | Toggle wajib-verifikasi email saat peneliti daftar akun. Matikan
    | (false) kalau domain pengirim belum terverifikasi di Resend — user
    | langsung aktif tanpa nunggu klik link, dan sistem tidak mencoba
    | kirim email sama sekali (jadi tidak akan gagal karena domain).
    |
    | Nyalakan (true) begitu MAIL_FROM_ADDRESS pakai domain yang sudah
    | diverifikasi di resend.com/domains.
    |
    */

    'email_verification_required' => env('EMAIL_VERIFICATION_REQUIRED', false),

];
