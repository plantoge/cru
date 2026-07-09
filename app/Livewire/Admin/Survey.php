<?php

namespace App\Livewire\Admin;

use App\Models\MasterAspek;
use App\Models\MasterPertanyaan;
use App\Models\MasterSkala;
use Livewire\Component;
use Mary\Traits\Toast;

class Survey extends Component
{
    use Toast;

    public string $tab = 'aspek';

    // Aspek
    public string $namaAspek = '';

    // Pertanyaan
    public string $teksPertanyaan = '';

    public ?string $aspekId = null;

    // Skala
    public string $namaSkala = '';

    public int $nilaiSkala = 0;

    public function tambahAspek()
    {
        $this->validate(['namaAspek' => 'required|string|max:150']);
        MasterAspek::create(['nama_aspek' => $this->namaAspek, 'urutan' => ((int) MasterAspek::max('urutan')) + 10]);
        $this->reset('namaAspek');
        $this->success('Aspek ditambahkan.');
    }

    public function tambahPertanyaan()
    {
        $this->validate(['teksPertanyaan' => 'required|string', 'aspekId' => 'required|uuid']);
        MasterPertanyaan::create([
            'master_aspek_id' => $this->aspekId,
            'pertanyaan' => $this->teksPertanyaan,
            'urutan' => ((int) MasterPertanyaan::where('master_aspek_id', $this->aspekId)->max('urutan')) + 10,
        ]);
        $this->reset('teksPertanyaan');
        $this->success('Pertanyaan ditambahkan.');
    }

    public function tambahSkala()
    {
        $this->validate(['namaSkala' => 'required|string|max:60', 'nilaiSkala' => 'required|integer']);
        MasterSkala::create(['nama_skala' => $this->namaSkala, 'nilai' => $this->nilaiSkala, 'urutan' => $this->nilaiSkala * 10]);
        $this->reset('namaSkala', 'nilaiSkala');
        $this->success('Skala ditambahkan.');
    }

    public function toggleAspek(string $id)
    {
        $a = MasterAspek::findOrFail($id);
        $a->update(['status_aktif' => ! $a->status_aktif]);
    }

    public function togglePertanyaan(string $id)
    {
        $p = MasterPertanyaan::findOrFail($id);
        $p->update(['status_aktif' => ! $p->status_aktif]);
    }

    public function hapus(string $model, string $id)
    {
        abort_unless(auth()->user()->can('master-survey.delete'), 403);

        match ($model) {
            'aspek' => MasterAspek::findOrFail($id)->delete(),
            'pertanyaan' => MasterPertanyaan::findOrFail($id)->delete(),
            'skala' => MasterSkala::findOrFail($id)->delete(),
        };

        $this->success('Terhapus.');
    }

    public function render()
    {
        return view('livewire.admin.survey', [
            'aspek' => MasterAspek::with('pertanyaan')->orderBy('urutan')->get(),
            'skala' => MasterSkala::orderBy('urutan')->get(),
        ])->title('Master Survey');
    }
}
