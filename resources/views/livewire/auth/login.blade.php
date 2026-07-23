<x-mary-card title="Masuk" subtitle="Gunakan akun terdaftar Anda" shadow>
    @if (session('status'))
        <x-mary-alert title="{{ session('status') }}" icon="o-check-circle" class="alert-success mb-4" />
    @endif
    <x-mary-form wire:submit="login">
        <x-mary-input label="Email" wire:model="email" icon="o-envelope" type="email" required />
        <x-mary-input label="Password" wire:model="password" icon="o-key" type="password" required />
        <div class="text-right -mt-2">
            <a href="{{ route('password.request') }}" class="text-sm link link-hover">Lupa password?</a>
        </div>
        <x-mary-checkbox label="Ingat saya" wire:model="remember" />
        <x-captcha :question="$captchaQuestion" />
        <x-slot:actions>
            <x-mary-button label="Daftar" link="{{ route('register') }}" class="btn-ghost" />
            <x-mary-button label="Masuk" type="submit" icon="o-arrow-right-end-on-rectangle" class="btn-primary" spinner="login" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
