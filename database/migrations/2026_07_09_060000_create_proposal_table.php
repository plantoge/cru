<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->unsignedSmallInteger('tahun');            // D6: nomor increment per tahun
            $t->unsignedBigInteger('nomor');
            $t->string('kode')->unique();                 // RSPISS-YYYY-###
            $t->string('peneliti_utama');
            $t->text('tim_peneliti')->nullable();
            $t->text('judul_penelitian');
            $t->string('institusi_asal')->nullable();     // snapshot pengaju
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->uuid('user_id');                          // relasi users (FK menyusul)
            $t->string('status');                         // cast enum ProposalStatus
            $t->string('unit_sekarang')->nullable();      // D2: turunan status, materialized
            $t->timestamp('tanggal_presentasi')->nullable();
            $t->string('kategori_presentasi')->nullable();
            $t->string('media_presentasi')->nullable();
            $t->boolean('isi_survey_kepuasan')->default(false);
            $t->timestamps();
            $t->softDeletes();
            $t->auditColumns();

            $t->unique(['tahun', 'nomor']);
            $t->index('unit_sekarang');
            $t->index('status');
            $t->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal');
    }
};
