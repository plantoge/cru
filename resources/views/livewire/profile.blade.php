<div class="max-w-2xl mx-auto space-y-6">
    <x-mary-card title="Informasi Akun" shadow>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="opacity-60">Nama</dt>
                <dd class="font-medium">{{ auth()->user()->name }}</dd>
            </div>
            <div>
                <dt class="opacity-60">Email</dt>
                <dd class="font-medium">{{ auth()->user()->email }}</dd>
            </div>
            <div>
                <dt class="opacity-60">No. HP / WhatsApp</dt>
                <dd class="font-medium">{{ auth()->user()->phone ?: '-' }}</dd>
            </div>
            <div>
                <dt class="opacity-60">Institusi Asal</dt>
                <dd class="font-medium">{{ auth()->user()->institusi_asal ?: '-' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="opacity-60">Peran</dt>
                <dd class="font-medium">{{ auth()->user()->getRoleNames()->implode(', ') ?: '-' }}</dd>
            </div>
        </dl>
    </x-mary-card>

    <x-mary-card title="Ubah Password" shadow>
        <x-mary-form wire:submit="ubahPassword">
            <x-mary-password label="Password Saat Ini" wire:model="current_password" icon="o-lock-closed" right required />
            <x-mary-password label="Password Baru" wire:model="password" icon="o-key" hint="Minimal 8 karakter" right required />
            <x-slot:actions>
                <x-mary-button label="Simpan Password" type="submit" icon="o-check" class="btn-primary" spinner="ubahPassword" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-card>
</div>
