<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\ProposalStatus as S;
use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Models\Respon;
use App\Models\User;
use App\Services\ProposalWorkflow;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SurveyGateTest extends TestCase
{
    use RefreshDatabase;

    protected User $peneliti;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('public');

        $this->peneliti = User::factory()->create();
        $this->peneliti->assignRole('peneliti');
        $this->actingAs($this->peneliti);
    }

    protected function proposalDenganIzinFinal(User $pemilik): array
    {
        $wf = app(ProposalWorkflow::class);

        $p = $wf->ajukan([
            'peneliti_utama' => 'X', 'judul_penelitian' => 'Y', 'user_id' => $pemilik->id,
        ]);

        foreach ([S::MenungguPresentasi, S::MenungguKelengkapanBerkasEtik, S::MenungguPenunjukanReviewer, S::MenungguReviewReviewer,
            S::DisetujuiReviewer, S::MenungguPembayaran, S::MenungguVerifikasiPembayaran,
            S::PelaksanaanPenelitian, S::MenungguVerifikasiAkhir, S::MenungguSurveyKepuasan] as $ke) {
            $wf->transition($p, $ke);
        }

        Storage::disk('public')->put("izin/{$p->id}.pdf", 'PDF');

        $doc = ProposalDocument::create([
            'proposal_id' => $p->id,
            'jenis' => DocumentType::IzinFinal->value,
            'path' => "izin/{$p->id}.pdf",
            'nama_asli' => 'izin-final.pdf',
        ]);

        return [$p, $doc];
    }

    public function test_izin_final_terkunci_sebelum_survey(): void
    {
        [$p, $doc] = $this->proposalDenganIzinFinal($this->peneliti);

        $this->get(route('dokumen.download', $doc))->assertForbidden();
    }

    public function test_izin_final_terbuka_setelah_survey(): void
    {
        [$p, $doc] = $this->proposalDenganIzinFinal($this->peneliti);

        Respon::create([
            'proposal_id' => $p->id,
            'responden_id' => $this->peneliti->id,
        ]);

        $this->get(route('dokumen.download', $doc))->assertOk();
    }

    public function test_survey_proposal_a_tidak_membuka_proposal_b(): void
    {
        [$pA, $docA] = $this->proposalDenganIzinFinal($this->peneliti);
        [$pB, $docB] = $this->proposalDenganIzinFinal($this->peneliti);

        // Survey hanya untuk proposal A (bug D5 yang ditutup)
        Respon::create(['proposal_id' => $pA->id, 'responden_id' => $this->peneliti->id]);

        $this->get(route('dokumen.download', $docA))->assertOk();
        $this->get(route('dokumen.download', $docB))->assertForbidden();
    }

    public function test_dokumen_orang_lain_ditolak(): void
    {
        [$p, $doc] = $this->proposalDenganIzinFinal($this->peneliti);

        $lain = User::factory()->create();
        $lain->assignRole('peneliti');

        $this->actingAs($lain)
            ->get(route('dokumen.download', $doc))
            ->assertForbidden();
    }
}
