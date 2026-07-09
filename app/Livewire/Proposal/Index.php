<?php

namespace App\Livewire\Proposal;

use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $cari = '';

    public function render()
    {
        $proposals = auth()->user()->proposals()
            ->when($this->cari, fn ($q) => $q->where(fn ($w) => $w
                ->where('kode', 'ilike', "%{$this->cari}%")
                ->orWhere('judul_penelitian', 'ilike', "%{$this->cari}%")))
            ->latest()
            ->paginate(10);

        return view('livewire.proposal.index', compact('proposals'))->title('Proposal Saya');
    }
}
