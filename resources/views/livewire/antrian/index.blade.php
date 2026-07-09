<div>
    <x-mary-header :title="$judul" subtitle="Diurut dari yang paling lama menunggu" separator>
        <x-slot:middle class="!justify-end">
            <x-mary-input placeholder="Cari kode / judul / peneliti..." wire:model.live.debounce="cari" icon="o-magnifying-glass" clearable />
        </x-slot:middle>
    </x-mary-header>

    <x-mary-card shadow>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Kode</th><th>Peneliti</th><th>Judul</th><th>Status</th><th>Update</th><th></th></tr></thead>
                <tbody>
                @forelse ($proposals as $p)
                    <tr>
                        <td class="font-mono">{{ $p->kode }}</td>
                        <td>{{ $p->peneliti_utama }}</td>
                        <td class="max-w-sm truncate">{{ $p->judul_penelitian }}</td>
                        <td><span class="badge badge-sm {{ $p->status->warna() }}">{{ $p->status->value }}</span></td>
                        <td>{{ $p->updated_at->diffForHumans() }}</td>
                        <td><x-mary-button label="Proses" icon="o-arrow-right" link="{{ route('proposal.show', $p) }}" class="btn-primary btn-sm" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center opacity-60">Antrian kosong. 🎉</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $proposals->links() }}
    </x-mary-card>
</div>
