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
            ->groupBy('status')->orderByDesc('jml')->pluck('jml', 'status');

        $tahunTersedia = Proposal::selectRaw('distinct tahun')->orderByDesc('tahun')->pluck('tahun');

        return view('livewire.laporan', [
            'total' => (clone $q)->count(),
            'selesai' => (clone $q)->where('status', ProposalStatus::Selesai->value)->count(),
            'ditolak' => (clone $q)->whereIn('status', [ProposalStatus::Ditolak->value, ProposalStatus::DitolakKajiEtik->value])->count(),
            'perStatus' => $perStatus,
            'tahunTersedia' => $tahunTersedia,
        ])->title('Laporan');
    }
}
