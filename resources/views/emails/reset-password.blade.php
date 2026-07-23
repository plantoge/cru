<x-mail::message>
# Reset Password Akun eProposal RSPI

Halo **{{ $user->name }}**,

Kami menerima permintaan reset password untuk akun Anda. Klik tombol di bawah untuk membuat password baru.

<x-mail::button :url="$url" color="primary">
Reset Password
</x-mail::button>

Tautan ini berlaku selama **60 menit**. Bila tombol tidak berfungsi, salin tautan ini ke browser:

{{ $url }}

Abaikan email ini bila Anda tidak meminta reset password — password Anda tidak akan berubah.

Terima kasih,<br>
Tim eProposal RSPI
</x-mail::message>
