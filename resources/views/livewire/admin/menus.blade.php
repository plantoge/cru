<div>
    <x-mary-header title="Manajemen Menu" subtitle="Menu dinamis — permission {slug}.aksi otomatis tersinkron" separator>
        <x-slot:actions>
            @can('menus.create')
                <x-mary-button label="Tambah Menu" icon="o-plus" wire:click="buka" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow>
        <table class="table">
            <thead><tr><th>Urutan</th><th>Nama</th><th>Slug</th><th>Route</th><th>Parent</th><th>Aktif</th><th></th></tr></thead>
            <tbody>
            @foreach ($menus as $m)
                <tr>
                    <td>{{ $m->urutan }}</td>
                    <td><x-mary-icon :name="$m->icon ?? 'o-minus-small'" class="w-4 h-4 inline mr-1" />{{ $m->nama }}</td>
                    <td class="font-mono text-xs">{{ $m->slug }}</td>
                    <td class="font-mono text-xs">{{ $m->route ?? '—' }}</td>
                    <td>{{ $m->parent?->nama ?? '—' }}</td>
                    <td>{!! $m->aktif ? '<span class="badge badge-success badge-sm">ya</span>' : '<span class="badge badge-ghost badge-sm">tidak</span>' !!}</td>
                    <td class="text-right whitespace-nowrap">
                        @can('menus.update')<x-mary-button icon="o-pencil" wire:click="buka('{{ $m->id }}')" class="btn-ghost btn-sm" />@endcan
                        @can('menus.delete')<x-mary-button icon="o-trash" wire:click="hapus('{{ $m->id }}')" wire:confirm="Hapus menu & permission terkait?" class="btn-ghost btn-sm text-error" />@endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </x-mary-card>

    <x-mary-modal wire:model="modal" :title="$editId ? 'Ubah Menu' : 'Tambah Menu'">
        <x-mary-form wire:submit="simpan">
            <x-mary-input label="Nama" wire:model="nama" required />
            <x-mary-input label="Slug" wire:model="slug" hint="Dasar nama permission: {slug}.read|create|update|delete" required />
            <x-mary-input label="Route name" wire:model="route" hint="mis. proposal.index" />
            <x-mary-input label="Icon" wire:model="icon" hint="heroicons, mis. o-home" />
            <x-mary-select label="Parent" wire:model="parent_id" placeholder="— tanpa parent —"
                :options="$menus->where('id', '!=', $editId)->map(fn ($m) => ['id' => $m->id, 'name' => $m->nama])->values()" />
            <div class="grid grid-cols-2 gap-3">
                <x-mary-input label="Urutan" wire:model="urutan" type="number" />
                <x-mary-checkbox label="Aktif" wire:model="aktif" class="mt-8" />
            </div>
            <x-slot:actions>
                <x-mary-button label="Batal" @click="$wire.modal = false" />
                <x-mary-button label="Simpan" type="submit" class="btn-primary" spinner="simpan" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
