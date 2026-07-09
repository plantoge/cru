<?php

namespace App\Enums;

enum Unit: string
{
    case Penelitian = 'penelitian';
    case KajiEtik = 'kaji_etik';
    case Reviewer = 'reviewer';

    public function label(): string
    {
        return match ($this) {
            self::Penelitian => 'CRU / Penelitian',
            self::KajiEtik => 'KEPK / Kaji Etik',
            self::Reviewer => 'Reviewer',
        };
    }
}
