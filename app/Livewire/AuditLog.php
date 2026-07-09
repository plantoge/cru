<?php

namespace App\Livewire;

use App\Models\ProposalStatusHistory;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLog extends Component
{
    use WithPagination;

    public string $cari = '';

    public function render()
    {
        $logs = ProposalStatusHistory::query()
            ->with(['proposal', 'actor'])
            ->when($this->cari, fn ($q) => $q->whereHas('proposal', fn ($w) => $w
                ->where('kode', 'ilike', "%{$this->cari}%")))
            ->latest('created_at')
            ->paginate(25);

        return view('livewire.audit-log', compact('logs'))->title('Audit Log');
    }
}
