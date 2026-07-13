<?php

namespace App\Models;

use App\Concerns\HasUuidAndAudit;
use App\Enums\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProposalMessage extends Model
{
    use HasUuidAndAudit, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['proposal_id', 'sender_id', 'sender_unit', 'pesan', 'dibaca_at'];

    protected $casts = [
        'sender_unit' => Unit::class,
        'dibaca_at' => 'datetime',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class, 'proposal_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
