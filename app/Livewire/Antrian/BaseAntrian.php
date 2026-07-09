<?php

namespace App\Livewire\Antrian;

use App\Enums\Unit;
use App\Models\Proposal;
use Livewire\Component;
use Livewire\WithPagination;

abstract class BaseAntrian extends Component
{
    use WithPagination;

    public string $cari = '';

    abstract protected function unit(): Unit;

    abstract protected function judul(): string;

    public function render()
    {
        $proposals = Proposal::query()
            ->where('unit_sekarang', $this->unit()->value)
            ->when($this->cari, fn ($q) => $q->where(fn ($w) => $w
                ->where('kode', 'ilike', "%{$this->cari}%")
                ->orWhere('judul_penelitian', 'ilike', "%{$this->cari}%")
                ->orWhere('peneliti_utama', 'ilike', "%{$this->cari}%")))
            ->oldest('updated_at')
            ->paginate(15);

        return view('livewire.antrian.index', [
            'proposals' => $proposals,
            'judul' => $this->judul(),
        ])->title($this->judul());
    }
}
