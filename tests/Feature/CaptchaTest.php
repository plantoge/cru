<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\Menu;
use App\Models\User;
use App\Services\MathCaptcha;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CaptchaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Menu::create(['nama' => 'Dashboard', 'slug' => 'dashboard', 'route' => 'dashboard']);
        Role::findByName('peneliti')->givePermissionTo('dashboard.read');
    }

    // ===== MathCaptcha service =====

    public function test_jawaban_benar_lolos_verifikasi(): void
    {
        $captcha = app(MathCaptcha::class);
        $soal = $captcha->generate();

        [$a, $op, $b] = explode(' ', $soal['question']);
        $jawaban = $op === '+' ? $a + $b : $a - $b;

        $this->assertTrue($captcha->verify($soal['id'], (string) $jawaban));
    }

    public function test_jawaban_salah_gagal_verifikasi(): void
    {
        $captcha = app(MathCaptcha::class);
        $soal = $captcha->generate();

        $this->assertFalse($captcha->verify($soal['id'], '999999'));
    }

    public function test_token_sekali_pakai_gagal_kalau_dipakai_ulang(): void
    {
        $captcha = app(MathCaptcha::class);
        $soal = $captcha->generate();

        [$a, $op, $b] = explode(' ', $soal['question']);
        $jawaban = (string) ($op === '+' ? $a + $b : $a - $b);

        $this->assertTrue($captcha->verify($soal['id'], $jawaban));
        $this->assertFalse($captcha->verify($soal['id'], $jawaban)); // replay ditolak
    }

    public function test_id_tak_dikenal_gagal_verifikasi(): void
    {
        $this->assertFalse(app(MathCaptcha::class)->verify('id-ngasal', '5'));
    }

    // ===== Login & Register terintegrasi =====

    public function test_login_gagal_kalau_jawaban_captcha_salah(): void
    {
        $user = User::factory()->create(['email' => 'ada@test.local']);
        $user->assignRole('peneliti');

        Livewire::test(Login::class)
            ->set('email', 'ada@test.local')
            ->set('password', 'password')
            ->set('captchaAnswer', '999999')
            ->call('login')
            ->assertHasErrors('captchaAnswer');
    }

    public function test_login_berhasil_kalau_captcha_benar(): void
    {
        $user = User::factory()->create(['email' => 'ada2@test.local', 'password' => 'password123']);
        $user->assignRole('peneliti');

        $test = Livewire::test(Login::class)
            ->set('email', 'ada2@test.local')
            ->set('password', 'password123');

        $captcha = app(MathCaptcha::class);
        $id = $test->get('captchaId');
        $jawaban = session('captcha_'.$id);

        $test->set('captchaAnswer', (string) $jawaban)
            ->call('login')
            ->assertRedirect(route('dashboard'));
    }

    public function test_registrasi_gagal_kalau_captcha_kosong(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Peneliti Uji')
            ->set('email', 'peneliti-uji@test.local')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors('captchaAnswer');

        $this->assertDatabaseMissing('users', ['email' => 'peneliti-uji@test.local']);
    }

    public function test_soal_captcha_berganti_setelah_login_gagal(): void
    {
        $test = Livewire::test(Login::class);
        $soalAwal = $test->get('captchaId');

        $test->set('email', 'gakada@test.local')
            ->set('password', 'salah')
            ->set('captchaAnswer', '999999')
            ->call('login');

        $this->assertNotSame($soalAwal, $test->get('captchaId'));
    }
}
