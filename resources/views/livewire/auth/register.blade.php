<x-mary-card title="Daftar Akun" subtitle="" shadow>
    <x-mary-form wire:submit="register">
        <x-mary-input label="Nama lengkap" wire:model="name" icon="o-user" required />
        <x-mary-input label="Email" wire:model="email" icon="o-envelope" type="email" required />
        <x-mary-password label="Kata Sandi" wire:model="password" icon="o-key" hint="Minimal 8 karakter" right required />
        <x-captcha :question="$captchaQuestion" />
        <x-slot:actions>
            <x-mary-button label="punya akun" link="{{ route('login') }}" class="btn-ghost" />
            <x-mary-button label="Daftar" type="submit" icon="o-user-plus" class="btn-primary" spinner="register" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
