<?php

namespace App\Models;

use App\Concerns\HasUuidAndAudit;
use App\Enums\ProposalStatus;
use App\Enums\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proposal extends Model
{
    use HasUuidAndAudit, SoftDeletes;

    protected $table = 'proposal';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tahun', 'nomor', 'kode',
        'peneliti_utama', 'tim_peneliti', 'judul_penelitian',
        'institusi_asal', 'email', 'phone', 'user_id',
        'status', 'unit_sekarang',
        'tanggal_presentasi', 'kategori_presentasi', 'media_presentasi',
        'isi_survey_kepuasan',
    ];

    protected $casts = [
        'status' => ProposalStatus::class,
        'unit_sekarang' => Unit::class,
        'tanggal_presentasi' => 'datetime',
        'isi_survey_kepuasan' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function documents()
    {
        return $this->hasMany(ProposalDocument::class, 'proposal_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(ProposalStatusHistory::class, 'proposal_id')->orderBy('created_at');
    }

    public function reviews()
    {
        return $this->hasMany(ProposalReview::class, 'proposal_id')->orderBy('ronde');
    }

    public function reviewerAssignments()
    {
        return $this->hasMany(ProposalReviewerAssignment::class, 'proposal_id');
    }

    /** Semua reviewer yang ditugaskan sudah ACC (syarat KEPK lanjut). */
    public function semuaReviewerAcc(): bool
    {
        return $this->reviewerAssignments()->exists()
            && $this->reviewerAssignments()->where('status', '!=', ProposalReviewerAssignment::ACC)->doesntExist();
    }

    public function respon()
    {
        return $this->hasOne(Respon::class, 'proposal_id');
    }

    /** Dokumen versi terakhir per jenis. */
    public function dokumenTerakhir(\App\Enums\DocumentType $jenis): ?ProposalDocument
    {
        return $this->documents()
            ->where('jenis', $jenis->value)
            ->orderByDesc('versi')
            ->first();
    }
}
