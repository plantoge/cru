<x-mary-card title="Lupa Password" subtitle="Masukkan email akun Anda, link reset akan dikirim" shadow>
    @if ($sent)
        <x-mary-alert title="Kalau email terdaftar, link reset password sudah dikirim. Silakan cek inbox."
            icon="o-check-circle" class="alert-success mb-4" />
        <x-mary-button label="Kembali ke Login" link="{{ route('login') }}" class="btn-primary w-full" />
    @else
        <x-mary-form wire:submit="kirimLinkReset">
            <x-mary-input label="Email" wire:model="email" icon="o-envelope" type="email" required />
            <x-captcha :question="$captchaQuestion" />
            <x-slot:actions>
                <x-mary-button label="Kembali ke Login" link="{{ route('login') }}" class="btn-ghost" />
                <x-mary-button label="Kirim Link Reset" type="submit" icon="o-paper-airplane" class="btn-primary" spinner="kirimLinkReset" />
            </x-slot:actions>
        </x-mary-form>
    @endif
</x-mary-card>
