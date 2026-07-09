<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_reviews', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('proposal_id');
            $t->unsignedTinyInteger('tahap');        // 1..4
            $t->string('unit');                      // enum Unit (D3)
            $t->uuid('reviewer_id')->nullable();
            $t->string('keputusan');                 // approve|revise|reject
            $t->text('komentar')->nullable();
            $t->unsignedSmallInteger('ronde')->default(1);
            $t->timestamps();
            $t->softDeletes();
            $t->auditColumns();

            $t->index(['proposal_id', 'ronde']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_reviews');
    }
};
