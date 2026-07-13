<?php

namespace Tests\Feature;

use App\Livewire\Auth\Register;
use App\Models\Menu;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Menu::create(['nama' => 'Dashboard', 'slug' => 'dashboard', 'route' => 'dashboard']);
        Role::findByName('peneliti')->givePermissionTo('dashboard.read');
    }

    // ===== Toggle ON: EMAIL_VERIFICATION_REQUIRED=true =====

    public function test_toggle_on_registrasi_kirim_notifikasi_verify_email(): void
    {
        config(['eproposal.email_verification_required' => true]);
        Notification::fake();

        Livewire::test(Register::class)
            ->set('name', 'Peneliti Baru')
            ->set('email', 'baru@test.local')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        $user = User::where('email', 'baru@test.local')->firstOrFail();

        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_toggle_on_user_belum_verified_diarahkan_ke_notice(): void
    {
        config(['eproposal.email_verification_required' => true]);

        $user = User::factory()->unverified()->create();
        $user->assignRole('peneliti');

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_toggle_on_klik_link_signed_menandai_verified_dan_bisa_akses(): void
    {
        config(['eproposal.email_verification_required' => true]);

        $user = User::factory()->unverified()->create();
        $user->assignRole('peneliti');

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)->get($url)->assertRedirect();

        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->actingAs($user->fresh())->get('/dashboard')->assertOk();
    }

    public function test_toggle_on_user_verified_tidak_diarahkan_ke_notice(): void
    {
        config(['eproposal.email_verification_required' => true]);

        $user = User::factory()->create(); // default verified
        $user->assignRole('peneliti');

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    // ===== Toggle OFF (default): EMAIL_VERIFICATION_REQUIRED=false =====

    public function test_toggle_off_registrasi_langsung_verified_tanpa_kirim_email(): void
    {
        config(['eproposal.email_verification_required' => false]);
        Notification::fake();

        Livewire::test(Register::class)
            ->set('name', 'Peneliti Baru')
            ->set('email', 'offtoggle@test.local')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        $user = User::where('email', 'offtoggle@test.local')->firstOrFail();

        $this->assertNotNull($user->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_toggle_off_user_belum_verified_tetap_bisa_akses(): void
    {
        config(['eproposal.email_verification_required' => false]);

        $user = User::factory()->unverified()->create();
        $user->assignRole('peneliti');

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }
}
