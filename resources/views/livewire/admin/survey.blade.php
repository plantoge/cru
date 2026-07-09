<div>
    <x-mary-header title="Master Survey Kepuasan" subtitle="Aspek · Pertanyaan · Skala" separator />

    <x-mary-tabs wire:model="tab">
        <x-mary-tab name="aspek" label="Aspek & Pertanyaan" icon="o-list-bullet">
            @can('master-survey.create')
                <div class="grid sm:grid-cols-2 gap-4 mb-4">
                    <x-mary-card title="Tambah Aspek" shadow>
                        <x-mary-form wire:submit="tambahAspek">
                            <x-mary-input label="Nama aspek" wire:model="namaAspek" required />
                            <x-slot:actions><x-mary-button label="Tambah" type="submit" class="btn-primary btn-sm" spinner="tambahAspek" /></x-slot:actions>
                        </x-mary-form>
                    </x-mary-card>
                    <x-mary-card title="Tambah Pertanyaan" shadow>
                        <x-mary-form wire:submit="tambahPertanyaan">
                            <x-mary-select label="Aspek" wire:model="aspekId" placeholder="Pilih aspek"
                                :options="$aspek->map(fn ($a) => ['id' => $a->id, 'name' => $a->nama_aspek])" required />
                            <x-mary-textarea label="Pertanyaan" wire:model="teksPertanyaan" rows="2" required />
                            <x-slot:actions><x-mary-button label="Tambah" type="submit" class="btn-primary btn-sm" spinner="tambahPertanyaan" /></x-slot:actions>
                        </x-mary-form>
                    </x-mary-card>
                </div>
            @endcan

            @foreach ($aspek as $a)
                <x-mary-card shadow class="mb-3">
                    <div class="flex items-center justify-between">
                        <div class="font-semibold">{{ $a->nama_aspek }}
                            @unless ($a->status_aktif)<span class="badge badge-ghost badge-sm ml-2">nonaktif</span>@endunless
                        </div>
                        <div>
                            @can('master-survey.update')<x-mary-button icon="o-power" wire:click="toggleAspek('{{ $a->id }}')" class="btn-ghost btn-xs" tooltip="Aktif/nonaktif" />@endcan
                            @can('master-survey.delete')<x-mary-button icon="o-trash" wire:click="hapus('aspek', '{{ $a->id }}')" wire:confirm="Hapus aspek beserta pertanyaannya?" class="btn-ghost btn-xs text-error" />@endcan
                        </div>
                    </div>
                    <ul class="mt-2 text-sm space-y-1">
                        @foreach ($a->pertanyaan as $p)
                            <li class="flex items-center justify-between border-b border-base-200 last:border-0 py-1">
                                <span class="{{ $p->status_aktif ? '' : 'opacity-40 line-through' }}">{{ $p->pertanyaan }}</span>
                                <span class="whitespace-nowrap">
                                    @can('master-survey.update')<x-mary-button icon="o-power" wire:click="togglePertanyaan('{{ $p->id }}')" class="btn-ghost btn-xs" />@endcan
                                    @can('master-survey.delete')<x-mary-button icon="o-trash" wire:click="hapus('pertanyaan', '{{ $p->id }}')" wire:confirm="Hapus pertanyaan?" class="btn-ghost btn-xs text-error" />@endcan
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </x-mary-card>
            @endforeach
        </x-mary-tab>

        <x-mary-tab name="skala" label="Skala" icon="o-star">
            @can('master-survey.create')
                <x-mary-card title="Tambah Skala" shadow class="mb-4 max-w-md">
                    <x-mary-form wire:submit="tambahSkala">
                        <x-mary-input label="Nama skala" wire:model="namaSkala" required />
                        <x-mary-input label="Nilai" wire:model="nilaiSkala" type="number" required />
                        <x-slot:actions><x-mary-button label="Tambah" type="submit" class="btn-primary btn-sm" spinner="tambahSkala" /></x-slot:actions>
                    </x-mary-form>
                </x-mary-card>
            @endcan

            <x-mary-card shadow class="max-w-md">
                <table class="table table-sm">
                    <thead><tr><th>Skala</th><th>Nilai</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($skala as $s)
                        <tr>
                            <td>{{ $s->nama_skala }}</td>
                            <td>{{ $s->nilai }}</td>
                            <td class="text-right">@can('master-survey.delete')<x-mary-button icon="o-trash" wire:click="hapus('skala', '{{ $s->id }}')" wire:confirm="Hapus skala?" class="btn-ghost btn-xs text-error" />@endcan</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </x-mary-card>
        </x-mary-tab>
    </x-mary-tabs>
</div>
