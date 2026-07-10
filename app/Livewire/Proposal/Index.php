<?php

namespace App\Livewire\Proposal;

use Livewire\Component;

class Index extends Component
{
    public int $perPage = 10;

    public string $cari = '';

    public function updatedCari(): void
    {
        $this->perPage = 10;
    }

    public function muatLagi(): void
    {
        $this->perPage += 10;
    }

    public function render()
    {
        $query = auth()->user()->proposals()
            ->when($this->cari, fn ($q) => $q->where(fn ($w) => $w
                ->where('kode', 'ilike', "%{$this->cari}%")
                ->orWhere('judul_penelitian', 'ilike', "%{$this->cari}%")))
            ->latest();

        $total = (clone $query)->count();
        $proposals = $query->take($this->perPage)->get();

        return view('livewire.proposal.index', [
            'proposals' => $proposals,
            'adaLagi' => $total > $proposals->count(),
        ])->title('Proposal Saya');
    }
}
