<div>
    <x-mary-header title="Dashboard" subtitle="Ringkasan penelitian" separator />

    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <x-mary-stat title="Total Proposal" :value="$stat['total']" icon="o-document-text" />
        <x-mary-stat title="Sedang Berjalan" :value="$stat['berjalan']" icon="o-arrow-path" class="text-info" />
        <x-mary-stat title="Selesai" :value="$stat['selesai']" icon="o-check-circle" class="text-success" />
        @canany(['antrian-cru.read', 'kaji-etik.read', 'antrian-reviewer.read'])
            <x-mary-stat title="Antrian CRU" :value="$stat['antrian_cru']" icon="o-inbox-stack" />
            <x-mary-stat title="Antrian KEPK" :value="$stat['antrian_kepk']" icon="o-scale" />
            <x-mary-stat title="Antrian Reviewer" :value="$stat['antrian_reviewer']" icon="o-clipboard-document-check" />
        @endcanany
    </div>

    @if ($milikSaya->isNotEmpty())
        <x-mary-card title="Proposal Saya Terbaru" class="mt-6" shadow>
            @foreach ($milikSaya as $p)
                <x-mary-list-item :item="$p" link="{{ route('proposal.show', $p) }}">
                    <x-slot:value>{{ $p->kode }} — {{ $p->judul_penelitian }}</x-slot:value>
                    <x-slot:sub-value>
                        <span class="badge badge-sm {{ $p->status->warna() }}">{{ $p->status->value }}</span>
                    </x-slot:sub-value>
                </x-mary-list-item>
            @endforeach
        </x-mary-card>
    @endif

    @role('peneliti')
        <div class="mt-6">
            <x-mary-button label="Ajukan Proposal Baru" icon="o-plus" link="{{ route('proposal.create') }}" class="btn-primary" />
        </div>
    @endrole
</div>
