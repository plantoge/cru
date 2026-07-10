<?php

namespace App\Livewire\Antrian;

use App\Enums\ProposalStatus;
use App\Enums\Unit;
use App\Models\Proposal;

class Kepk extends BaseAntrian
{
    protected function unit(): Unit
    {
        return Unit::KajiEtik;
    }

    protected function judul(): string
    {
        return 'Antrian Kaji Etik';
    }

    /**
     * Unit kaji_etik + proposal yang sedang direview (KEPK memantau
     * tanggapan reviewer dan meneruskan revisinya ke peneliti).
     */
    protected function query()
    {
        return Proposal::query()->where(fn ($q) => $q
            ->where('unit_sekarang', Unit::KajiEtik->value)
            ->orWhere('status', ProposalStatus::MenungguReviewReviewer->value));
    }
}
