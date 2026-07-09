<?php

namespace App\Livewire\Admin;

use App\Models\InformasiKontak;
use Livewire\Component;
use Mary\Traits\Toast;

class Kontak extends Component
{
    use Toast;

    public array $data = [];

    protected const FIELDS = [
        'telepon', 'fax', 'callcenter', 'hotline', 'email', 'alamat', 'deskripsi_alamat',
        'facebook', 'instagram', 'twitter', 'whatsapp',
        'cp_kaji_etik', 'wa_kaji_etik', 'cp_pks', 'wa_pks', 'cp_mta', 'wa_mta',
        'cp_kerahasiaan', 'wa_kerahasiaan',
        'pemilik_rekening', 'nomor_rekening', 'nama_bank', 'deskripsi_biaya',
    ];

    public function mount()
    {
        $kontak = InformasiKontak::current();
        foreach (self::FIELDS as $f) {
            $this->data[$f] = $kontak->{$f} ?? '';
        }
    }

    public function simpan()
    {
        abort_unless(auth()->user()->can('informasi-kontak.update'), 403);

        InformasiKontak::current()->update($this->data);
        $this->success('Informasi kontak tersimpan.');
    }

    public function render()
    {
        return view('livewire.admin.kontak')->title('Informasi Kontak');
    }
}
