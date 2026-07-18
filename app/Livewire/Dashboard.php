<?php

namespace App\Livewire;

use App\Enums\ProposalStatus;
use App\Enums\Unit;
use App\Models\Proposal;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();

        $stat = [
            'total' => Proposal::count(),
            'berjalan' => Proposal::whereNotIn('status', [
                ProposalStatus::Selesai->value, ProposalStatus::Ditolak->value,
                ProposalStatus::DitolakKajiEtik->value, ProposalStatus::Dibatalkan->value,
            ])->count(),
            'selesai' => Proposal::where('status', ProposalStatus::Selesai->value)->count(),
            'antrian_cru' => Proposal::where('unit_sekarang', Unit::Penelitian->value)->count(),
            'antrian_kepk' => Proposal::where('unit_sekarang', Unit::KajiEtik->value)->count(),
            'antrian_reviewer' => Proposal::where('unit_sekarang', Unit::Reviewer->value)->count(),
        ];

        // Pemisah ribuan gaya Indonesia — tanpa ini angka besar tampil "1000000".
        $stat = array_map(fn (int $n) => number_format($n, 0, ',', '.'), $stat);

        $milikSaya = $user->hasRole('peneliti')
            ? $user->proposals()->latest()->limit(5)->get()
            : collect();

        return view('livewire.dashboard', compact('stat', 'milikSaya'))->title('Dashboard');
    }
}
