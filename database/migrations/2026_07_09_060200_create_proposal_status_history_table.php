<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_status_history', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('proposal_id');
            $t->string('from_status')->nullable();
            $t->string('to_status');
            $t->string('unit')->nullable();          // enum Unit (D3)
            $t->uuid('actor_id')->nullable();
            $t->text('catatan')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->auditColumns();

            $t->index('proposal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_status_history');
    }
};
