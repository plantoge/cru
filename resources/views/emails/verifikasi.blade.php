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