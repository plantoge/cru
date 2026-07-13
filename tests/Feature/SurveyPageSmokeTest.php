<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SurveySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SurveyPageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_master_survey_render(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(SurveySeeder::class);
        Menu::create(['nama' => 'Master Survey', 'slug' => 'master-survey', 'route' => 'admin.survey']);
        Menu::create(['nama' => 'Dashboard', 'slug' => 'dashboard', 'route' => 'dashboard']);

        $admin = User::factory()->create();
        $admin->assignRole('superadmin');
        Role::findByName('superadmin')->givePermissionTo(['master-survey.read', 'master-survey.create', 'master-survey.update', 'master-survey.delete', 'dashboard.read']);

        $this->actingAs($admin)->get('/admin/survey')->assertOk();
    }
}
