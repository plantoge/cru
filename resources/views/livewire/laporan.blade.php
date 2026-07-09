<div>
    <x-mary-header title="Laporan Penelitian" separator>
        <x-slot:actions>
            <x-mary-select wire:model.live="tahun" :options="$tahunTersedia->map(fn ($t) => ['id' => $t, 'name' => $t])" />
        </x-slot:actions>
    </x-mary-header>

    <div class="grid grid-cols-3 gap-4 mb-6">
        <x-mary-stat title="Total {{ $tahun }}" :value="$total" icon="o-document-text" />
        <x-mary-stat title="Selesai" :value="$selesai" icon="o-check-circle" class="text-success" />
        <x-mary-stat title="Ditolak" :value="$ditolak" icon="o-x-circle" class="text-error" />
    </div>

    <x-mary-card title="Sebaran Status" shadow>
        <table class="table table-sm">
            <thead><tr><th>Status</th><th class="text-right">Jumlah</th></tr></thead>
            <tbody>
            @forelse ($perStatus as $status => $jml)
                <tr><td>{{ $status }}</td><td class="text-right font-mono">{{ $jml }}</td></tr>
            @empty
                <tr><td colspan="2" class="text-center opacity-60">Belum ada data tahun ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </x-mary-card>
</div>
