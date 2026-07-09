<x-mary-card title="Masuk" subtitle="Gunakan akun terdaftar Anda" shadow>
    <x-mary-form wire:submit="login">
        <x-mary-input label="Email" wire:model="email" icon="o-envelope" type="email" required />
        <x-mary-input label="Password" wire:model="password" icon="o-key" type="password" required />
        <x-mary-checkbox label="Ingat saya" wire:model="remember" />
        <x-slot:actions>
            <x-mary-button label="Daftar sebagai peneliti" link="{{ route('register') }}" class="btn-ghost" />
            <x-mary-button label="Masuk" type="submit" icon="o-arrow-right-end-on-rectangle" class="btn-primary" spinner="login" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
