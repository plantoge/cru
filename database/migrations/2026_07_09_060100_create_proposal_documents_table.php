<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_documents', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('proposal_id');                 // relasi proposal (FK menyusul)
            $t->string('jenis');                     // enum DocumentType
            $t->string('path');                      // lokasi file di storage
            $t->string('nama_asli')->nullable();
            $t->unsignedSmallInteger('versi')->default(1);
            $t->uuid('uploaded_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->auditColumns();

            $t->index(['proposal_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_documents');
    }
};
