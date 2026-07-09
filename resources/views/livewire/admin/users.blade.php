<div>
    <x-mary-header title="Users" separator>
        <x-slot:middle class="!justify-end">
            <x-mary-input placeholder="Cari nama / email..." wire:model.live.debounce="cari" icon="o-magnifying-glass" clearable />
        </x-slot:middle>
        <x-slot:actions>
            @can('users.create')
                <x-mary-button label="Tambah" icon="o-plus" wire:click="buka" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow>
        <table class="table">
            <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th></th></tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    <td>@foreach ($u->getRoleNames() as $r)<span class="badge badge-sm badge-outline mr-1">{{ $r }}</span>@endforeach</td>
                    <td class="text-right whitespace-nowrap">
                        @can('users.update')<x-mary-button icon="o-pencil" wire:click="buka('{{ $u->id }}')" class="btn-ghost btn-sm" />@endcan
                        @can('users.delete')<x-mary-button icon="o-trash" wire:click="hapus('{{ $u->id }}')" wire:confirm="Hapus user ini?" class="btn-ghost btn-sm text-error" />@endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $users->links() }}
    </x-mary-card>

    <x-mary-modal wire:model="modal" :title="$editId ? 'Ubah User' : 'Tambah User'">
        <x-mary-form wire:submit="simpan">
            <x-mary-input label="Nama" wire:model="name" required />
            <x-mary-input label="Email" wire:model="email" type="email" required />
            <x-mary-input label="Password {{ $editId ? '(kosongkan bila tetap)' : '' }}" wire:model="password" type="password" />
            <x-mary-choices-offline label="Role" wire:model="roles" :options="$semuaRole->map(fn ($r) => ['id' => $r, 'name' => $r])" compact />
            <x-slot:actions>
                <x-mary-button label="Batal" @click="$wire.modal = false" />
                <x-mary-button label="Simpan" type="submit" class="btn-primary" spinner="simpan" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
