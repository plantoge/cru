<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Models\ProposalStatusHistory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Satu-satunya pintu perubahan status proposal (prd §7b).
 * Transisi dijaga canGoTo(); setiap perpindahan tercatat di
 * proposal_status_history.
 */
class ProposalWorkflow
{
    /**
     * Buat proposal baru dengan status awal Menunggu Verifikasi Berkas.
     */
    public function ajukan(array $data): Proposal
    {
        return DB::transaction(function () use ($data) {
            [$tahun, $nomor, $kode] = $this->generateKode();

            $status = ProposalStatus::MenungguVerifikasiBerkas;

            $proposal = Proposal::create([
                ...$data,
                'tahun' => $tahun,
                'nomor' => $nomor,
                'kode' => $kode,
                'user_id' => $data['user_id'] ?? Auth::id(),
                'status' => $status,
                'unit_sekarang' => $status->unit(),
            ]);

            $this->catatHistory($proposal, null, $status, 'Pengajuan proposal');

            return $proposal;
        });
    }

    /**
     * Pindahkan status. Abort 403 bila transisi tidak sah (cegah loncat).
     */
    public function transition(Proposal $proposal, ProposalStatus $ke, ?string $catatan = null): Proposal
    {
        $dari = $proposal->status;

        abort_unless($dari->canGoTo($ke), 403, "Transisi tidak sah: {$dari->value} → {$ke->value}");

        return DB::transaction(function () use ($proposal, $dari, $ke, $catatan) {
            $proposal->status = $ke;
            $proposal->unit_sekarang = $ke->unit();

            if ($ke === ProposalStatus::Selesai) {
                $proposal->isi_survey_kepuasan = true;
            }

            $proposal->save();

            $this->catatHistory($proposal, $dari, $ke, $catatan);

            return $proposal;
        });
    }

    /**
     * Simpan file dokumen; versi bertambah otomatis per jenis.
     */
    public function simpanDokumen(Proposal $proposal, DocumentType $jenis, UploadedFile $file): ProposalDocument
    {
        $versi = (int) $proposal->documents()
            ->where('jenis', $jenis->value)
            ->max('versi') + 1;

        $path = $file->store("proposal/{$proposal->id}/{$jenis->value}", 'public');

        return ProposalDocument::create([
            'proposal_id' => $proposal->id,
            'jenis' => $jenis->value,
            'path' => $path,
            'nama_asli' => $file->getClientOriginalName(),
            'versi' => $versi,
            'uploaded_by' => Auth::id(),
        ]);
    }

    /**
     * Kode proposal format RSPISS-YYYY-### (D6), nomor increment per tahun.
     * Lock baris tahun berjalan agar bebas race.
     *
     * @return array{0:int,1:int,2:string}
     */
    public function generateKode(): array
    {
        $tahun = (int) now()->year;

        // PG melarang FOR UPDATE + agregat; pakai advisory lock per tahun
        // (rilis otomatis saat transaksi selesai). Unique(tahun,nomor) tetap
        // jadi jaring pengaman terakhir.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('select pg_advisory_xact_lock(?)', [$tahun]);
        }

        $nomor = (int) Proposal::withTrashed()
            ->where('tahun', $tahun)
            ->max('nomor') + 1;

        $kode = sprintf('RSPISS-%d-%03d', $tahun, $nomor);

        return [$tahun, $nomor, $kode];
    }

    protected function catatHistory(Proposal $proposal, ?ProposalStatus $dari, ProposalStatus $ke, ?string $catatan): void
    {
        ProposalStatusHistory::create([
            'proposal_id' => $proposal->id,
            'from_status' => $dari?->value,
            'to_status' => $ke->value,
            'unit' => $ke->unit()?->value,
            'actor_id' => Auth::id(),
            'catatan' => $catatan,
        ]);
    }
}
