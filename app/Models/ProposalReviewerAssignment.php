<?php

namespace App\Models;

use App\Concerns\HasUuidAndAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProposalReviewerAssignment extends Model
{
    use HasUuidAndAudit, SoftDeletes;

    protected $table = 'proposal_reviewers';

    public $incrementing = false;

    protected $keyType = 'string';

    public const MENUNGGU = 'menunggu';

    public const ACC = 'acc';

    public const REVISI = 'revisi';

    protected $fillable = ['proposal_id', 'reviewer_id', 'status'];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class, 'proposal_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
