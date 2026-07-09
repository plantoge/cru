<div>
    <x-mary-header title="Informasi Kontak & Pembayaran" subtitle="Ditampilkan ke peneliti (rekening pada Tahap 3)" separator />

    <x-mary-form wire:submit="simpan">
        <div class="grid lg:grid-cols-2 gap-6">
            <x-mary-card title="Kontak" shadow>
                <x-mary-input label="Telepon" wire:model="data.telepon" />
                <x-mary-input label="Email" wire:model="data.email" />
                <x-mary-input label="Hotline" wire:model="data.hotline" />
                <x-mary-input label="WhatsApp" wire:model="data.whatsapp" />
                <x-mary-textarea label="Alamat" wire:model="data.alamat" rows="2" />
            </x-mary-card>

            <x-mary-card title="Pembayaran" shadow>
                <x-mary-input label="Nama bank" wire:model="data.nama_bank" />
                <x-mary-input label="Nomor rekening" wire:model="data.nomor_rekening" />
                <x-mary-input label="Pemilik rekening" wire:model="data.pemilik_rekening" />
                <x-mary-textarea label="Deskripsi biaya" wire:model="data.deskripsi_biaya" rows="3" />
            </x-mary-card>

            <x-mary-card title="Contact Person Layanan" shadow class="lg:col-span-2">
                <div class="grid sm:grid-cols-2 gap-3">
                    <x-mary-input label="CP Kaji Etik" wire:model="data.cp_kaji_etik" />
                    <x-mary-input label="WA Kaji Etik" wire:model="data.wa_kaji_etik" />
                    <x-mary-input label="CP PKS" wire:model="data.cp_pks" />
                    <x-mary-input label="WA PKS" wire:model="data.wa_pks" />
                    <x-mary-input label="CP Kerahasiaan" wire:model="data.cp_kerahasiaan" />
                    <x-mary-input label="WA Kerahasiaan" wire:model="data.wa_kerahasiaan" />
                </div>
            </x-mary-card>
        </div>

        <x-slot:actions>
            @can('informasi-kontak.update')
                <x-mary-button label="Simpan" type="submit" icon="o-check" class="btn-primary" spinner="simpan" />
            @endcan
        </x-slot:actions>
    </x-mary-form>
</div>
