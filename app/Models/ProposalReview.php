<?php

namespace App\Models;

use App\Concerns\HasUuidAndAudit;
use App\Enums\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProposalReview extends Model
{
    use HasUuidAndAudit, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'proposal_id', 'tahap', 'unit', 'reviewer_id', 'keputusan', 'komentar', 'ronde',
    ];

    protected $casts = [
        'unit' => Unit::class,
        'tahap' => 'integer',
        'ronde' => 'integer',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class, 'proposal_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
