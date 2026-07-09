<?php

namespace App\Livewire\Antrian;

use App\Enums\Unit;

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
}
