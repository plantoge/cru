<?php

namespace App\Livewire;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Livewire\Component;

class Laporan extends Component
{
    public string $tahun = '';

    public function render()
    {
        $this->tahun = $this->tahun ?: (string) now()->year;

        $q = Proposal::where('tahun', (int) $this->tahun);

        $perStatus = (clone $q)->selectRaw('status, count(*) as jml')
            ->groupBy('status')->orderByDesc('jml')
            ->pluck('jml', 'status')
            ->map(fn (int $n) => number_format($n, 0, ',', '.'));

        $tahunTersedia = Proposal::selectRaw('distinct tahun')->orderByDesc('tahun')->pluck('tahun');

        // Pemisah ribuan gaya Indonesia — konsisten dengan Dashboard.
        $angka = fn (int $n) => number_format($n, 0, ',', '.');

        return view('livewire.laporan', [
            'total' => $angka((clone $q)->count()),
            'selesai' => $angka((clone $q)->where('status', ProposalStatus::Selesai->value)->count()),
            'ditolak' => $angka((clone $q)->whereIn('status', [ProposalStatus::Ditolak->value, ProposalStatus::DitolakKajiEtik->value])->count()),
            'perStatus' => $perStatus,
            'tahunTersedia' => $tahunTersedia,
        ])->title('Laporan');
    }
}
