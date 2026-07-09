<div>
    <x-mary-header title="Audit Log" subtitle="Riwayat perpindahan status seluruh proposal" separator>
        <x-slot:middle class="!justify-end">
            <x-mary-input placeholder="Cari kode proposal..." wire:model.live.debounce="cari" icon="o-magnifying-glass" clearable />
        </x-slot:middle>
    </x-mary-header>

    <x-mary-card shadow>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>Waktu</th><th>Proposal</th><th>Dari</th><th>Ke</th><th>Unit</th><th>Aktor</th><th>Catatan</th></tr></thead>
                <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="font-mono">{{ $log->proposal?->kode }}</td>
                        <td>{{ $log->from_status?->value ?? '—' }}</td>
                        <td>{{ $log->to_status->value }}</td>
                        <td>{{ $log->unit?->value ?? '—' }}</td>
                        <td>{{ $log->actor?->name ?? 'Sistem' }}</td>
                        <td class="max-w-xs truncate">{{ $log->catatan }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center opacity-60">Belum ada aktivitas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    </x-mary-card>
</div>
