<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_messages', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('proposal_id');               // relasi proposal (FK menyusul)
            $t->uuid('sender_id');                  // users.id pengirim
            $t->string('sender_unit')->nullable();  // enum Unit snapshot saat kirim; null = peneliti
            $t->text('pesan');
            $t->timestamp('dibaca_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->auditColumns();

            $t->index(['proposal_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_messages');
    }
};
