<x-mary-card title="Daftar Peneliti" subtitle="Akun baru otomatis berperan peneliti" shadow>
    <x-mary-form wire:submit="register">
        <x-mary-input label="Nama lengkap" wire:model="name" icon="o-user" required />
        <x-mary-input label="Email" wire:model="email" icon="o-envelope" type="email" required />
        <x-mary-input label="No. HP / WhatsApp" wire:model="phone" icon="o-phone" />
        <x-mary-input label="Institusi asal" wire:model="institusi_asal" icon="o-building-library" />
        <x-mary-input label="Password" wire:model="password" icon="o-key" type="password" required />
        <x-mary-input label="Ulangi password" wire:model="password_confirmation" icon="o-key" type="password" required />
        <x-captcha :question="$captchaQuestion" />
        <x-slot:actions>
            <x-mary-button label="Sudah punya akun" link="{{ route('login') }}" class="btn-ghost" />
            <x-mary-button label="Daftar" type="submit" icon="o-user-plus" class="btn-primary" spinner="register" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
