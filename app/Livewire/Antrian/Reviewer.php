<?php

namespace App\Livewire\Antrian;

use App\Enums\Unit;

class Reviewer extends BaseAntrian
{
    protected function unit(): Unit
    {
        return Unit::Reviewer;
    }

    protected function judul(): string
    {
        return 'Antrian Reviewer';
    }
}
