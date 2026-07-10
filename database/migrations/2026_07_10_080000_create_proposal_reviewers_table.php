<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penugasan reviewer oleh KEPK (bisa >1 reviewer per proposal).
        Schema::create('proposal_reviewers', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('proposal_id');
            $t->uuid('reviewer_id');
            $t->string('status')->default('menunggu'); // menunggu|acc|revisi
            $t->timestamps();
            $t->softDeletes();
            $t->auditColumns();

            $t->index(['reviewer_id', 'status']);
        });

        DB::statement('create unique index proposal_reviewers_unique on proposal_reviewers (proposal_id, reviewer_id) where deleted_at is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_reviewers');
    }
};
