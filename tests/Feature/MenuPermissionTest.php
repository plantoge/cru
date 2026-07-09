<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MenuPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_buat_menu_membuat_4_permission(): void
    {
        Menu::create(['nama' => 'Contoh', 'slug' => 'contoh']);

        foreach (['read', 'create', 'update', 'delete'] as $aksi) {
            $this->assertDatabaseHas('permissions', ['name' => "contoh.{$aksi}"]);
        }
    }

    public function test_rename_slug_menyinkronkan_permission(): void
    {
        $menu = Menu::create(['nama' => 'Contoh', 'slug' => 'contoh']);
        $menu->update(['slug' => 'baru']);

        $this->assertDatabaseHas('permissions', ['name' => 'baru.read']);
        $this->assertDatabaseMissing('permissions', ['name' => 'contoh.read']);
    }

    public function test_hapus_menu_menghapus_permission(): void
    {
        $menu = Menu::create(['nama' => 'Contoh', 'slug' => 'contoh']);
        $menu->delete();

        $this->assertSame(0, Permission::where('name', 'like', 'contoh.%')->count());
    }

    public function test_route_terlindungi_permission(): void
    {
        Menu::create(['nama' => 'Antrian CRU', 'slug' => 'antrian-cru', 'route' => 'antrian.cru']);
        Menu::create(['nama' => 'Dashboard', 'slug' => 'dashboard', 'route' => 'dashboard']);

        $peneliti = User::factory()->create();
        $peneliti->assignRole('peneliti'); // tanpa antrian-cru.read

        $this->actingAs($peneliti)->get('/antrian/cru')->assertForbidden();

        $cru = User::factory()->create();
        $cru->assignRole('cru');
        Role::findByName('cru')->givePermissionTo(['antrian-cru.read', 'dashboard.read']);

        $this->actingAs($cru)->get('/antrian/cru')->assertOk();
    }
}
