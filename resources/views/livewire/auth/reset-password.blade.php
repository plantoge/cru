<x-mary-card title="Reset Password" subtitle="Buat password baru untuk akun Anda" shadow>
    <p class="text-sm opacity-70 mb-4">Reset password untuk: <span class="font-semibold">{{ $email }}</span></p>

    @if ($invalid)
        <x-mary-alert title="Tautan reset tidak valid atau sudah kedaluwarsa."
            icon="o-exclamation-triangle" class="alert-error mb-4" />
        <x-mary-button label="Minta Link Baru" link="{{ route('password.request') }}" class="btn-primary w-full" />
    @else
        <x-mary-form wire:submit="resetPassword">
            <x-mary-password label="Password Baru" wire:model="password" icon="o-key" hint="Minimal 8 karakter" right required />
            <x-slot:actions>
                <x-mary-button label="Kembali ke Login" link="{{ route('login') }}" class="btn-ghost" />
                <x-mary-button label="Simpan Password" type="submit" icon="o-check" class="btn-primary" spinner="resetPassword" />
            </x-slot:actions>
        </x-mary-form>
    @endif
</x-mary-card>
