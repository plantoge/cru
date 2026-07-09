<div>
    <x-mary-header title="Role & Permission" subtitle="Matriks hak akses role × menu (prd §5.2)" separator>
        <x-slot:actions>
            <x-mary-select wire:model.live="role" :options="$semuaRole->map(fn ($r) => ['id' => $r, 'name' => $r])" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr><th>Menu</th>@foreach ($aksi as $a)<th class="text-center capitalize">{{ $a }}</th>@endforeach<th class="text-center">Semua</th></tr>
                </thead>
                <tbody>
                @foreach ($menus as $menu)
                    <tr>
                        <td class="font-medium">{{ $menu->nama }} <span class="text-xs opacity-50 font-mono">{{ $menu->slug }}</span></td>
                        @foreach ($aksi as $a)
                            <td class="text-center">
                                <input type="checkbox" class="checkbox checkbox-sm checkbox-primary"
                                    wire:model="matrix.{{ $menu->slug }}.{{ $a }}">
                            </td>
                        @endforeach
                        <td class="text-center">
                            <x-mary-button icon="o-check-badge" wire:click="centangSemua('{{ $menu->slug }}')" class="btn-ghost btn-xs" tooltip="Centang/kosongkan semua" />
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <x-slot:actions>
            @can('roles.update')
                <x-mary-button label="Simpan Hak Akses" wire:click="simpan" icon="o-check" class="btn-primary" spinner="simpan" />
            @endcan
        </x-slot:actions>
    </x-mary-card>
</div>
