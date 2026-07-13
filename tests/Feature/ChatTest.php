<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus as S;
use App\Events\ProposalMessageSent;
use App\Livewire\Proposal\Chat;
use App\Models\Menu;
use App\Models\Proposal;
use App\Models\ProposalReviewerAssignment;
use App\Models\User;
use App\Services\ProposalWorkflow;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected ProposalWorkflow $wf;

    protected User $peneliti;

    protected User $cru;

    protected User $kepk;

    protected User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->wf = app(ProposalWorkflow::class);

        $this->peneliti = User::factory()->create();
        $this->peneliti->assignRole('peneliti');
        $this->cru = User::factory()->create();
        $this->cru->assignRole('cru');
        $this->kepk = User::factory()->create();
        $this->kepk->assignRole('kepk');
        $this->reviewer = User::factory()->create();
        $this->reviewer->assignRole('reviewer');

        // RoleSeeder cuma bikin role kosong — permission digenerate MenuObserver
        // saat Menu dibuat (sengaja gak seed MenuSeeder penuh, gak relevan di sini).
        Menu::create(['nama' => 'Antrian CRU', 'slug' => 'antrian-cru', 'route' => 'antrian.cru']);
        Menu::create(['nama' => 'Kaji Etik', 'slug' => 'kaji-etik', 'route' => 'antrian.kepk']);
        Menu::create(['nama' => 'Antrian Reviewer', 'slug' => 'antrian-reviewer', 'route' => 'antrian.reviewer']);
        Role::findByName('cru')->givePermissionTo('antrian-cru.read');
        Role::findByName('kepk')->givePermissionTo('kaji-etik.read');
        Role::findByName('reviewer')->givePermissionTo('antrian-reviewer.read');

        // broadcast()->toOthers() nge-dispatch pas __destruct() — timingnya gak
        // deterministik dalam siklus test Livewire. Event::fake() bikin dispatch
        // itu no-op (aman) tanpa mengubah logic kirim() yang diuji.
        Event::fake([ProposalMessageSent::class]);
    }

    protected function buatProposal(): Proposal
    {
        $this->actingAs($this->peneliti);

        return $this->wf->ajukan([
            'peneliti_utama' => 'X', 'judul_penelitian' => 'Y', 'user_id' => $this->peneliti->id,
        ]);
    }

    public function test_pemilik_boleh_chat(): void
    {
        $p = $this->buatProposal();
        $this->assertTrue($p->bisaChat($this->peneliti));
    }

    public function test_cru_boleh_chat(): void
    {
        $p = $this->buatProposal();
        $this->assertTrue($p->bisaChat($this->cru));
    }

    public function test_kepk_boleh_chat(): void
    {
        $p = $this->buatProposal();
        $this->assertTrue($p->bisaChat($this->kepk));
    }

    public function test_reviewer_tidak_boleh_chat_meski_ditugaskan(): void
    {
        $p = $this->buatProposal();

        // Reviewer ditugaskan resmi ke proposal ini (boleh LIHAT halaman)...
        ProposalReviewerAssignment::create(['proposal_id' => $p->id, 'reviewer_id' => $this->reviewer->id]);

        // ...tapi TETAP tidak boleh chat — kerahasiaan identitas reviewer.
        $this->assertFalse($p->bisaChat($this->reviewer));
    }

    public function test_orang_tak_terkait_tidak_boleh_chat(): void
    {
        $p = $this->buatProposal();
        $lain = User::factory()->create();
        $lain->assignRole('peneliti');

        $this->assertFalse($p->bisaChat($lain));
    }

    public function test_mount_403_untuk_reviewer(): void
    {
        $p = $this->buatProposal();
        ProposalReviewerAssignment::create(['proposal_id' => $p->id, 'reviewer_id' => $this->reviewer->id]);

        // Panggil mount() langsung (bukan Livewire::test()) — komponen non-full-page
        // tidak mem-propagate exception dari mount() ke pemanggil test dengan benar
        // di versi Livewire ini (diverifikasi via debug run terpisah).
        $this->actingAs($this->reviewer);
        $this->expectException(HttpException::class);
        (new Chat())->mount($p);
    }

    /** Defense-in-depth: widget chat sengaja tidak di-embed sama sekali di
     *  halaman proposal untuk reviewer (guard di show.blade.php), jadi
     *  mount() Chat.php di atas seharusnya tidak pernah tersentuh lewat UI. */
    public function test_widget_chat_tak_muncul_di_halaman_proposal_untuk_reviewer(): void
    {
        $p = $this->buatProposal();
        ProposalReviewerAssignment::create(['proposal_id' => $p->id, 'reviewer_id' => $this->reviewer->id]);

        $this->actingAs($this->reviewer)
            ->get(route('proposal.show', $p))
            ->assertOk()
            ->assertDontSee('Diskusi');
    }

    public function test_kirim_pesan_tersimpan_dan_tampil_di_riwayat(): void
    {
        $p = $this->buatProposal();

        $this->actingAs($this->cru);
        Livewire::test(Chat::class, ['proposal' => $p])
            ->set('pesan', 'Mohon lengkapi surat pengantar')
            ->call('kirim')
            ->assertSet('pesan', '');

        $this->assertDatabaseHas('proposal_messages', [
            'proposal_id' => $p->id,
            'sender_id' => $this->cru->id,
            'pesan' => 'Mohon lengkapi surat pengantar',
        ]);
    }

    public function test_pesan_kosong_ditolak(): void
    {
        $p = $this->buatProposal();

        $this->actingAs($this->peneliti);
        Livewire::test(Chat::class, ['proposal' => $p])
            ->set('pesan', '')
            ->call('kirim')
            ->assertHasErrors('pesan');
    }

    public function test_isolasi_pesan_antar_proposal(): void
    {
        $pA = $this->buatProposal();
        $pB = $this->buatProposal();

        $this->actingAs($this->peneliti);
        Livewire::test(Chat::class, ['proposal' => $pA])->set('pesan', 'Pesan khusus proposal A')->call('kirim');

        $this->actingAs($this->cru);
        $testB = Livewire::test(Chat::class, ['proposal' => $pB]);

        $this->assertCount(0, $testB->get('riwayat'));
        $this->assertSame(1, $pA->fresh()->messages()->count());
        $this->assertSame(0, $pB->fresh()->messages()->count());
    }

    public function test_riwayat_lama_termuat_saat_mount(): void
    {
        $p = $this->buatProposal();

        $this->actingAs($this->peneliti);
        Livewire::test(Chat::class, ['proposal' => $p])->set('pesan', 'Halo CRU')->call('kirim');

        $this->actingAs($this->cru);
        $test = Livewire::test(Chat::class, ['proposal' => $p]);

        $this->assertCount(1, $test->get('riwayat'));
        $this->assertSame('Halo CRU', $test->get('riwayat')[0]['pesan']);
    }

    public function test_bisa_chat_setelah_lolos_ke_kepk(): void
    {
        $p = $this->buatProposal();
        $this->wf->transition($p, S::MenungguPresentasi);
        $this->wf->transition($p, S::MenungguKelengkapanBerkasEtik);

        // CRU tetap bisa baca riwayat lama walau unit_sekarang sudah pindah ke KEPK
        $this->assertTrue($p->fresh()->bisaChat($this->cru));
        $this->assertTrue($p->fresh()->bisaChat($this->kepk));
    }
}
