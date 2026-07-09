<?php

namespace App\Livewire\Antrian;

use App\Enums\Unit;

class Cru extends BaseAntrian
{
    protected function unit(): Unit
    {
        return Unit::Penelitian;
    }

    protected function judul(): string
    {
        return 'Antrian CRU';
    }
}
