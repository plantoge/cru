<div>
    <x-mary-header :title="$judul" :subtitle="$riwayat ? 'Riwayat — semua proposal yang pernah melewati unit ini' : 'Diurut dari yang paling lama menunggu'" separator>
        <x-slot:middle class="!justify-end">
            <x-mary-input placeholder="Cari kode / judul / peneliti..." wire:model.live.debounce="cari" icon="o-magnifying-glass" clearable />
        </x-slot:middle>
    </x-mary-header>

    <div class="tabs tabs-box w-fit mb-4">
        <a class="tab {{ $tab === 'antrian' ? 'tab-active' : '' }}" wire:click="$set('tab', 'antrian')">
            <x-mary-icon name="o-inbox-stack" class="w-4 h-4 mr-1" /> Antrian Aktif
        </a>
        <a class="tab {{ $tab === 'riwayat' ? 'tab-active' : '' }}" wire:click="$set('tab', 'riwayat')">
            <x-mary-icon name="o-clock" class="w-4 h-4 mr-1" /> Riwayat
        </a>
    </div>

    <x-mary-card shadow>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Kode</th><th>Peneliti</th><th>Judul</th><th>Tahap</th><th>Status</th><th>Update</th><th></th></tr></thead>
                <tbody>
                @forelse ($proposals as $p)
                    <tr>
                        <td class="font-mono">{{ $p->kode }}</td>
                        <td>{{ $p->peneliti_utama }}</td>
                        <td class="max-w-sm truncate">{{ $p->judul_penelitian }}</td>
                        <td>{{ $p->status->tahapan() ? 'T'.$p->status->tahapan() : '—' }}</td>
                        <td><span class="badge badge-sm {{ $p->status->warna() }}">{{ $p->status->value }}</span></td>
                        <td>{{ $p->updated_at->diffForHumans() }}</td>
                        <td>
                            @if ($riwayat)
                                <x-mary-button label="Lihat" icon="o-eye" link="{{ route('proposal.show', $p) }}" class="btn-ghost btn-sm" />
                            @else
                                <x-mary-button label="Proses" icon="o-arrow-right" link="{{ route('proposal.show', $p) }}" class="btn-primary btn-sm" />
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center opacity-60">
                        {{ $riwayat ? 'Belum ada proposal yang melewati unit ini.' : 'Antrian kosong. 🎉' }}
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($adaLagi)
            {{-- Infinite scroll: saat sentinel terlihat, muat 15 baris berikutnya --}}
            <div x-intersect="$wire.muatLagi()" class="py-4 text-center" wire:key="sentinel-{{ $tab }}-{{ $proposals->count() }}">
                <span class="loading loading-dots loading-md opacity-50"></span>
            </div>
        @elseif ($proposals->isNotEmpty())
            <div class="py-3 text-center text-xs opacity-40">Semua data sudah ditampilkan ({{ $proposals->count() }})</div>
        @endif
    </x-mary-card>
</div>
