<?php

namespace App\Livewire\Proposal;

use App\Enums\DocumentType;
use App\Enums\ProposalStatus;
use App\Enums\Unit;
use App\Models\MasterAspek;
use App\Models\MasterSkala;
use App\Models\Proposal;
use App\Models\ProposalReview;
use App\Models\Respon;
use App\Services\ProposalWorkflow;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class Show extends Component
{
    use Toast, WithFileUploads;

    public Proposal $proposal;

    public string $catatan = '';

    // Presentasi (CRU)
    public string $tanggal_presentasi = '';

    public string $kategori_presentasi = '';

    public string $media_presentasi = '';

    // Upload generik per aksi
    public $fileUpload;          // satu file (surat tanggapan/penolakan/izin/bukti bayar/revisi proposal)

    public $fileEtik = [];       // form_kaji_etik, informed_consent, pks, kerahasiaan_data

    public $fileLaporan;

    public $fileRawData;

    // Survey
    public array $jawabanSurvey = []; // pertanyaan_id => skala_id

    public string $saran = '';

    public function mount(Proposal $proposal)
    {
        $user = auth()->user();

        abort_unless(
            $proposal->user_id === $user->id
            || $user->canAny(['antrian-cru.read', 'kaji-etik.read', 'antrian-reviewer.read']),
            403,
        );

        $this->proposal = $proposal;
    }

    protected function pemilik(): bool
    {
        return $this->proposal->user_id === auth()->id();
    }

    protected function pindah(ProposalStatus $ke, ?string $catatan = null): void
    {
        app(ProposalWorkflow::class)->transition($this->proposal, $ke, $catatan ?: null);
        $this->proposal->refresh();
        $this->reset('catatan', 'fileUpload', 'fileEtik', 'fileLaporan', 'fileRawData');
        $this->success("Status: {$ke->value}");
    }

    protected function simpanFile(DocumentType $jenis, $file): void
    {
        app(ProposalWorkflow::class)->simpanDokumen($this->proposal, $jenis, $file);
    }

    // ============ Aksi Peneliti ============

    /** Perbaiki proposal (T1): re-upload → Menunggu Verifikasi Revisi. */
    public function kirimRevisi()
    {
        abort_unless($this->pemilik(), 403);
        $this->validate(['fileUpload' => 'required|'.DocumentType::Proposal->aturanValidasi()]);

        $this->simpanFile(DocumentType::Proposal, $this->fileUpload);
        $this->pindah(ProposalStatus::MenungguVerifikasiRevisi, $this->catatan);
    }

    /** Lengkapi 4 berkas etik (T2) → Menunggu Review Reviewer. */
    public function kirimBerkasEtik()
    {
        abort_unless($this->pemilik(), 403);

        $rules = [];
        foreach (DocumentType::wajibTahap2() as $jenis) {
            $rules["fileEtik.{$jenis->value}"] = 'required|'.$jenis->aturanValidasi();
        }
        $this->validate($rules);

        foreach (DocumentType::wajibTahap2() as $jenis) {
            $this->simpanFile($jenis, $this->fileEtik[$jenis->value]);
        }

        $this->pindah(ProposalStatus::MenungguReviewReviewer, $this->catatan);
    }

    /** Perbaiki berkas etik sesuai komentar reviewer (loop, opsional per berkas). */
    public function kirimRevisiEtik()
    {
        abort_unless($this->pemilik(), 403);

        $adaFile = false;
        foreach (DocumentType::wajibTahap2() as $jenis) {
            if (! empty($this->fileEtik[$jenis->value])) {
                $this->validate(["fileEtik.{$jenis->value}" => $jenis->aturanValidasi()]);
                $this->simpanFile($jenis, $this->fileEtik[$jenis->value]);
                $adaFile = true;
            }
        }

        if (! $adaFile) {
            $this->addError('fileEtik', 'Unggah minimal satu berkas revisi.');

            return;
        }

        $this->pindah(ProposalStatus::MenungguReviewReviewer, $this->catatan);
    }

    /** Upload bukti bayar (T3) → Menunggu Verifikasi Pembayaran. */
    public function kirimBuktiBayar()
    {
        abort_unless($this->pemilik(), 403);
        $this->validate(['fileUpload' => 'required|'.DocumentType::BuktiBayar->aturanValidasi()]);

        $this->simpanFile(DocumentType::BuktiBayar, $this->fileUpload);
        $this->pindah(ProposalStatus::MenungguVerifikasiPembayaran);
    }

    /** Upload laporan + raw data (T4) → Menunggu Verifikasi Akhir. */
    public function kirimLaporan()
    {
        abort_unless($this->pemilik(), 403);
        $this->validate([
            'fileLaporan' => 'required|'.DocumentType::LaporanPenelitian->aturanValidasi(),
            'fileRawData' => 'required|'.DocumentType::RawData->aturanValidasi(),
        ]);

        $this->simpanFile(DocumentType::LaporanPenelitian, $this->fileLaporan);
        $this->simpanFile(DocumentType::RawData, $this->fileRawData);
        $this->pindah(ProposalStatus::MenungguVerifikasiAkhir);
    }

    /** Isi survey kepuasan (gate) → Selesai; izin final terbuka. */
    public function kirimSurvey()
    {
        abort_unless($this->pemilik(), 403);
        abort_unless($this->proposal->status === ProposalStatus::MenungguSurveyKepuasan, 403);

        $wajib = MasterAspek::where('status_aktif', true)->with('pertanyaan')->get()
            ->flatMap->pertanyaan->where('status_aktif', true)->where('is_required', true);

        foreach ($wajib as $p) {
            if (empty($this->jawabanSurvey[$p->id])) {
                $this->addError('jawabanSurvey', 'Semua pertanyaan wajib dijawab.');

                return;
            }
        }

        $user = auth()->user();

        $respon = Respon::create([
            'proposal_id' => $this->proposal->id,
            'responden_id' => $user->id,
            'responden' => $user->name,
            'jenis_responden' => 'peneliti',
            'saran' => $this->saran,
        ]);

        $skala = MasterSkala::pluck('nama_skala', 'id');
        $teks = $wajib->pluck('pertanyaan', 'id');

        foreach ($this->jawabanSurvey as $pertanyaanId => $skalaId) {
            $respon->jawaban()->create([
                'master_pertanyaan_id' => $pertanyaanId,
                'master_skala_id' => $skalaId,
                'pertanyaan' => $teks[$pertanyaanId] ?? null,
                'jawaban' => $skala[$skalaId] ?? null,
            ]);
        }

        $this->pindah(ProposalStatus::Selesai, 'Survey kepuasan diisi');
    }

    // ============ Aksi CRU ============

    public function mintaRevisi()
    {
        abort_unless(auth()->user()->can('antrian-cru.update'), 403);

        if ($this->fileUpload) {
            $this->validate(['fileUpload' => DocumentType::SuratTanggapan->aturanValidasi()]);
            $this->simpanFile(DocumentType::SuratTanggapan, $this->fileUpload);
        }

        $this->pindah(ProposalStatus::PerluRevisiProposal, $this->catatan);
    }

    public function mintaPresentasi()
    {
        abort_unless(auth()->user()->can('antrian-cru.update'), 403);
        $this->validate([
            'tanggal_presentasi' => 'required|date',
            'kategori_presentasi' => 'required|string',
            'media_presentasi' => 'required|string',
        ]);

        $this->proposal->fill([
            'tanggal_presentasi' => $this->tanggal_presentasi,
            'kategori_presentasi' => $this->kategori_presentasi,
            'media_presentasi' => $this->media_presentasi,
        ])->save();

        $this->pindah(ProposalStatus::MenungguPresentasi, $this->catatan);
    }

    public function tolak()
    {
        abort_unless(auth()->user()->can('antrian-cru.update'), 403);
        $this->validate(['fileUpload' => 'required|'.DocumentType::SuratPenolakan->aturanValidasi()]);

        $this->simpanFile(DocumentType::SuratPenolakan, $this->fileUpload);
        $this->pindah(ProposalStatus::Ditolak, $this->catatan);
    }

    public function loloskan()
    {
        abort_unless(auth()->user()->can('antrian-cru.update'), 403);
        $this->pindah(ProposalStatus::MenungguKelengkapanBerkasEtik, $this->catatan ?: 'Lolos ke KEPK');
    }

    /** Verifikasi bukti bayar + terbit draft izin → Pelaksanaan Penelitian. */
    public function terbitkanDraftIzin()
    {
        abort_unless(auth()->user()->can('antrian-cru.update'), 403);
        $this->validate(['fileUpload' => 'required|'.DocumentType::IzinDraft->aturanValidasi()]);

        $this->simpanFile(DocumentType::IzinDraft, $this->fileUpload);
        $this->pindah(ProposalStatus::PelaksanaanPenelitian, $this->catatan);
    }

    /** D4: bukti bayar tidak sah → kembali Menunggu Pembayaran. */
    public function tolakBuktiBayar()
    {
        abort_unless(auth()->user()->can('antrian-cru.update'), 403);
        $this->pindah(ProposalStatus::MenungguPembayaran, $this->catatan ?: 'Bukti pembayaran ditolak');
    }

    /** Terbit izin final (unduh terkunci survey) → Menunggu Survey Kepuasan. */
    public function terbitkanIzinFinal()
    {
        abort_unless(auth()->user()->can('antrian-cru.update'), 403);
        $this->validate(['fileUpload' => 'required|'.DocumentType::IzinFinal->aturanValidasi()]);

        $this->simpanFile(DocumentType::IzinFinal, $this->fileUpload);
        $this->pindah(ProposalStatus::MenungguSurveyKepuasan, $this->catatan);
    }

    /** D4: laporan/raw data kurang → kembali Pelaksanaan Penelitian. */
    public function tolakLaporan()
    {
        abort_unless(auth()->user()->can('antrian-cru.update'), 403);
        $this->pindah(ProposalStatus::PelaksanaanPenelitian, $this->catatan ?: 'Laporan perlu diperbaiki');
    }

    public function batalkan()
    {
        abort_unless(auth()->user()->can('antrian-cru.update'), 403);
        $this->pindah(ProposalStatus::Dibatalkan, $this->catatan ?: 'Dibatalkan');
    }

    // ============ Aksi Reviewer ============

    protected function catatReview(string $keputusan): void
    {
        $ronde = (int) $this->proposal->reviews()->where('unit', Unit::Reviewer->value)->max('ronde');

        ProposalReview::create([
            'proposal_id' => $this->proposal->id,
            'tahap' => 2,
            'unit' => Unit::Reviewer->value,
            'reviewer_id' => auth()->id(),
            'keputusan' => $keputusan,
            'komentar' => $this->catatan,
            'ronde' => $keputusan === 'revise' ? $ronde + 1 : max($ronde, 1),
        ]);
    }

    public function reviewerMintaRevisi()
    {
        abort_unless(auth()->user()->can('antrian-reviewer.update'), 403);
        $this->validate(['catatan' => 'required|string'], [], ['catatan' => 'komentar']);

        $this->catatReview('revise');
        $this->pindah(ProposalStatus::PerluRevisiReviewer, $this->catatan);
    }

    public function reviewerAcc()
    {
        abort_unless(auth()->user()->can('antrian-reviewer.update'), 403);

        $this->catatReview('approve');
        $this->pindah(ProposalStatus::DisetujuiReviewer, $this->catatan ?: 'ACC Reviewer');
    }

    // ============ Aksi KEPK ============

    public function kepkLanjut()
    {
        abort_unless(auth()->user()->can('kaji-etik.update'), 403);
        $this->pindah(ProposalStatus::MenungguPembayaran, $this->catatan ?: 'Lanjut ke pembayaran');
    }

    public function kepkTolak()
    {
        abort_unless(auth()->user()->can('kaji-etik.update'), 403);
        $this->validate(['catatan' => 'required|string'], [], ['catatan' => 'alasan penolakan']);

        $this->catatReview('reject');
        $this->pindah(ProposalStatus::DitolakKajiEtik, $this->catatan);
    }

    public function render()
    {
        $s = $this->proposal->status;

        return view('livewire.proposal.show', [
            'dokumen' => $this->proposal->documents()->orderBy('jenis')->orderByDesc('versi')->get()->groupBy('jenis'),
            'history' => $this->proposal->statusHistory()->with('actor')->get(),
            'reviews' => $this->proposal->reviews()->with('reviewer')->latest('created_at')->get(),
            'aspekSurvey' => $s === ProposalStatus::MenungguSurveyKepuasan && $this->pemilik()
                ? MasterAspek::where('status_aktif', true)->orderBy('urutan')
                    ->with(['pertanyaan' => fn ($q) => $q->where('status_aktif', true)->orderBy('urutan')])->get()
                : collect(),
            'skalaSurvey' => MasterSkala::orderBy('urutan')->get(),
            'kontak' => \App\Models\InformasiKontak::query()->first(),
            'isCru' => auth()->user()->can('antrian-cru.update'),
            'isKepk' => auth()->user()->can('kaji-etik.update'),
            'isReviewer' => auth()->user()->can('antrian-reviewer.update'),
            'isPemilik' => $this->pemilik(),
        ])->title($this->proposal->kode);
    }
}
