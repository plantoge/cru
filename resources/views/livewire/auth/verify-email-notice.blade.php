<x-mary-card title="Verifikasi Email" subtitle="Satu langkah lagi sebelum bisa akses aplikasi" shadow>
    <p class="text-sm opacity-70 mb-4">
        Kami sudah kirim link verifikasi ke <b>{{ auth()->user()->email }}</b>.
        Klik link di email tersebut untuk mengaktifkan akun Anda.
    </p>

    @if ($terkirim)
        <x-mary-alert icon="o-check-circle" class="alert-success mb-4">
            Link verifikasi baru sudah dikirim.
        </x-mary-alert>
    @endif

    <x-slot:actions>
        <x-mary-button label="Keluar" wire:click="logout" class="btn-ghost" />
        <x-mary-button label="Kirim Ulang Link" wire:click="kirimUlang" icon="o-paper-airplane" class="btn-primary" spinner="kirimUlang" />
    </x-slot:actions>
</x-mary-card>
