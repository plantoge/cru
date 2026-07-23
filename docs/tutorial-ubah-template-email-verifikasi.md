# Tutorial — Ubah Tampilan Email Verifikasi (Kamu yang Kerjakan)

> Panduan langkah-demi-langkah untuk mengubah **tampilan email verifikasi akun** yang masuk ke inbox peneliti.
> Ditulis supaya kamu bisa kerjakan sendiri. Ikuti urut.

---

## 0. Pahami dulu: 2 hal yang JANGAN dicampur

Ini sumber bingung nomor satu — baca pelan:

| Yang mau diubah | Di mana | Catatan |
|---|---|---|
| **Tampilan email** (teks, warna, logo, tombol) | View / template | Bagian yang kamu ubah di tutorial ini |
| **Field `email_verified_at`** | **BUKAN di template** | Ke-update OTOMATIS saat user klik link di email |

**Kunci:** kamu tidak menaruh logika `email_verified_at` di dalam email. Field itu terisi sendiri karena:

```
User klik tombol di email
   → link mengarah ke route 'verification.verify'
   → VerifyEmailController jalan
   → markEmailAsVerified() isi kolom email_verified_at
```

Jadi tugasmu di template cuma satu soal fungsi: **pastikan tombol memakai variabel `$url` yang benar** (URL bertanda-tangan / signed dari Laravel). Sisanya (desain) bebas. Selama tombol pakai `$url` itu, field otomatis ter-update.

> ⚠️ Kalau kamu bikin link sendiri asal ketik (mis. `https://.../verify`), tanda tangannya tidak valid → user dapat error **403**. Selalu pakai `$url` yang disediakan (lihat langkah).

---

## 1. WAJIB dilakukan dulu: buang `dd()` di Register

Buka [app/Livewire/Auth/Register.php](../app/Livewire/Auth/Register.php). Kalau ada baris ini di awal method `register()`:

```php
public function register()
{
    dd(config('eproposal.email_verification_required'));   // ← HAPUS baris ini
    try {
```

`dd()` **menghentikan** seluruh proses daftar — tidak ada akun dibuat, tidak ada email terkirim. Itu sisa debug. Hapus barisnya sampai jadi:

```php
public function register()
{
    try {
```

---

## Pilih jalur

- **Cara A** — cuma mau ganti teks & subjek (bahasa Indonesia, nama RSPI). Cepat, 5 menit. → §2
- **Cara B** — mau desain HTML/Blade sendiri sepenuhnya (logo, warna, layout). Lebih panjang. → §3

Mulai dari Cara A. Kalau kurang, naik ke Cara B.

---

## 2. CARA A — Ubah teks & subjek

Fitur Laravel: `VerifyEmail::toMailUsing()`. Kamu daftarkan sekali di service provider, berlaku untuk semua email verifikasi.

### Langkah 2.1
Buka [app/Providers/AppServiceProvider.php](../app/Providers/AppServiceProvider.php).

### Langkah 2.2
Tambahkan dua baris `use` di atas (di bawah `namespace`):

```php
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
```

### Langkah 2.3
Di dalam method `boot()`, tempel blok ini:

```php
VerifyEmail::toMailUsing(function ($notifiable, string $url) {
    return (new MailMessage)
        ->subject('Verifikasi Akun eProposal RSPI')
        ->greeting('Halo ' . $notifiable->name . ',')
        ->line('Terima kasih sudah mendaftar di eProposal RSPI.')
        ->line('Klik tombol di bawah untuk mengaktifkan akun Anda.')
        ->action('Verifikasi Email', $url)   // ← $url ini yang memicu email_verified_at
        ->line('Abaikan email ini bila Anda tidak merasa mendaftar.');
});
```

### Yang boleh kamu ubah
- `->subject('...')` — judul email.
- `->greeting('...')` — sapaan pembuka.
- `->line('...')` — kalimat. Tambah/kurangi sesuka hati.
- `->action('Teks Tombol', $url)` — teks tombol. **`$url` JANGAN diganti** — itu tiket verifikasi.

### Langkah 2.4 — terapkan
```bash
php artisan config:clear
```
Daftar akun baru → cek email masuk. Selesai. Tampilan masih memakai bingkai HTML default Laravel (rapi, tapi generik). Kalau mau ganti logo/warna/layout → lanjut Cara B.

---

## 3. CARA B — Template HTML/Blade sendiri (kendali penuh)

Tiga file: (1) beri tahu User pakai notifikasi kustom, (2) bikin kelas notifikasi, (3) bikin Blade-nya.

### Langkah 3.1 — arahkan User ke notifikasi kustom
Buka [app/Models/User.php](../app/Models/User.php). Tambah method ini di dalam `class User`:

```php
public function sendEmailVerificationNotification(): void
{
    $this->notify(new \App\Notifications\VerifikasiEmailRSPI);
}
```

Artinya: saat Laravel mau kirim email verifikasi, pakai kelas buatanmu, bukan bawaan.

### Langkah 3.2 — bikin kelas notifikasi
Bikin file baru `app/Notifications/VerifikasiEmailRSPI.php`:

```php
<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifikasiEmailRSPI extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // URL signed — INI yang memicu update email_verified_at saat diklik.
        // Jangan bikin URL manual; harus lewat temporarySignedRoute biar valid.
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );

        return (new MailMessage)
            ->subject('Verifikasi Akun eProposal RSPI')
            ->markdown('emails.verifikasi', [
                'url'  => $url,
                'user' => $notifiable,
            ]);
    }
}
```

> Angka `60` = link berlaku 60 menit. Ubah sesuai kebutuhan.

### Langkah 3.3 — bikin template Blade
Bikin file baru `resources/views/emails/verifikasi.blade.php`:

```blade
<x-mail::message>
# Verifikasi Akun eProposal RSPI

Halo **{{ $user->name }}**,

Terima kasih sudah mendaftar. Klik tombol di bawah untuk mengaktifkan akun Anda.

<x-mail::button :url="$url" color="primary">
Verifikasi Email
</x-mail::button>

Bila tombol tidak berfungsi, salin tautan ini ke browser:

{{ $url }}

Abaikan email ini bila Anda tidak merasa mendaftar.

Terima kasih,<br>
Tim eProposal RSPI
</x-mail::message>
```

- `<x-mail::message>` & `<x-mail::button>` = komponen email bawaan Laravel (sudah ada, tak perlu install).
- `:url="$url"` — **wajib** pakai `$url` dari langkah 3.2. Ini penyambung ke `email_verified_at`.
- Isi teks/heading bebas kamu ubah.

### Langkah 3.4 — uji
```bash
php artisan config:clear
```
Daftar akun baru → cek email. Sekarang tampil template buatanmu.

---

## 4. Ganti logo & warna semua email (opsional, untuk Cara B)

Kalau mau ubah header (logo "Laravel" → nama RSPI), warna tombol, dan footer untuk **semua** email:

```bash
php artisan vendor:publish --tag=laravel-mail
```

Muncul folder `resources/views/vendor/mail/html/`. Yang sering diedit:
- `html/header.blade.php` — ganti tulisan/logo di kepala email.
- `html/themes/default.css` — warna, font, tombol.
- `html/footer.blade.php` — teks kaki email.

Berlaku ke semua email notifikasi (verifikasi, reset password, dll).

---

## 5. Cara menguji tanpa daftar berulang

Setelah domain `rspiss.com` **Verified** dan `.env` benar:

```env
MAIL_FROM_ADDRESS="simrs@rspiss.com"        # domain WAJIB rspiss.com (yg verified)
EMAIL_VERIFICATION_REQUIRED=true
```
```bash
php artisan config:clear
```

Lalu:
1. Daftar akun baru → email masuk ke inbox.
2. Cek juga di **Resend → menu Logs / Emails** — semua email tercatat status kirimnya di sana.
3. Klik tombol di email → harusnya diarahkan ke dashboard & kolom `email_verified_at` user terisi.

Cek field terisi (opsional, lewat tinker):
```bash
php artisan tinker
>>> \App\Models\User::where('email','emailkamu@test.com')->value('email_verified_at');
```
Kalau ada tanggal → verifikasi sukses.

---

## 6. Ringkas / Checklist

- [ ] Hapus `dd()` di `Register.php` (§1) — **wajib**, kalau tidak proses daftar mati.
- [ ] Cara A: `toMailUsing()` di `AppServiceProvider::boot()` (§2), atau
- [ ] Cara B: 3 file — `User.php` + `VerifikasiEmailRSPI.php` + `verifikasi.blade.php` (§3).
- [ ] Tombol email **selalu** pakai variabel `$url` (jangan link manual).
- [ ] `php artisan config:clear` tiap habis ubah.
- [ ] `MAIL_FROM_ADDRESS` di domain `rspiss.com` (yang verified).
- [ ] Uji: daftar → email masuk → klik → `email_verified_at` terisi.

## Rujukan Laravel Docs
- Email Verification → section *"Customizing The Verification Email"* — `laravel.com/docs/12.x/verification`
- Notifications → *"Mail Notifications"* & *"Customizing The Templates"* — `laravel.com/docs/12.x/notifications`

**File terkait:** `app/Livewire/Auth/Register.php` (picu), `app/Http/Controllers/Auth/VerifyEmailController.php` (yang isi `email_verified_at`), `config/eproposal.php` (toggle). Setup Resend & DNS: `docs/setup-resend-email.md`.
