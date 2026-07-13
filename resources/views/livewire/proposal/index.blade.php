<div>
    <x-mary-header title="Proposal Saya" separator>
        <x-slot:middle class="!justify-end">
            <x-mary-input placeholder="Cari kode / judul..." wire:model.live.debounce="cari" icon="o-magnifying-glass" clearable />
        </x-slot:middle>
        <x-slot:actions>
            @can('proposal.create')
                <x-mary-button label="Ajukan Baru" icon="o-plus" link="{{ route('proposal.create') }}" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow>
        <div class="overflow-x-auto overflow-y-auto max-h-[65vh]">
            <table class="table table-pin-rows">
                <thead><tr><th>Kode</th><th>Judul</th><th>Tahap</th><th>Status</th><th>Diajukan</th><th></th></tr></thead>
                <tbody>
                @forelse ($proposals as $p)
                    <tr>
                        <td class="font-mono">{{ $p->kode }}</td>
                        <td class="max-w-md truncate">{{ $p->judul_penelitian }}</td>
                        <td>{{ $p->status->tahapan() ? 'Tahap '.$p->status->tahapan() : '—' }}</td>
                        <td><span class="badge badge-sm {{ $p->status->warna() }}">{{ $p->status->value }}</span></td>
                        <td>{{ $p->created_at->format('d/m/Y') }}</td>
                        <td><x-mary-button icon="o-eye" link="{{ route('proposal.show', $p) }}" class="btn-ghost btn-sm" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center opacity-60">Belum ada proposal.</td></tr>
                @endforelse
                </tbody>
            </table>

            @if ($adaLagi)
                <div x-intersect="$wire.muatLagi()" class="py-4 text-center" wire:key="sentinel-{{ $proposals->count() }}">
                    <span class="loading loading-dots loading-md opacity-50"></span>
                </div>
            @elseif ($proposals->isNotEmpty())
                <div class="py-3 text-center text-xs opacity-40">Semua data sudah ditampilkan ({{ $proposals->count() }})</div>
            @endif
        </div>
    </x-mary-card>
</div>
