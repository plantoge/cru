<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait HasUuidAndAudit
{
    protected static function bootHasUuidAndAudit(): void
    {
        static::creating(function ($model) {
            if (! $model->id) {
                $model->id = (string) Str::uuid7();
            }

            if (Auth::check()) {
                $model->created_by ??= Auth::id();
                $model->updated_by ??= Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
                $model->deleted_by = Auth::id();
                $model->saveQuietly(); // hindari memicu event updating lagi
            }
        });
    }

    public function initializeHasUuidAndAudit(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }
}
