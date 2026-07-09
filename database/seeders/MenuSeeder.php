<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MenuSeeder extends Seeder
{
    /**
     * Menu awal + matriks permission default per role (prd §3.1 & §5).
     * Format matriks: role => [aksi, ...] — 'crud' = read+create+update+delete.
     */
    public function run(): void
    {
        $menus = [
            // [nama, slug, route, icon, matriks role => aksi]
            ['Dashboard', 'dashboard', 'dashboard', 'o-home', [
                'superadmin' => 'crud', 'direksi' => ['read'], 'cru' => ['read'],
                'administrator' => ['read'], 'peneliti' => ['read'], 'reviewer' => ['read'],
                'kepk' => ['read'], 'rekam_medis' => ['read'], 'auditor' => ['read'],
            ]],
            ['Proposal Saya', 'proposal', 'proposal.index', 'o-document-text', [
                'superadmin' => 'crud', 'peneliti' => ['read', 'create', 'update'],
            ]],
            ['Antrian CRU', 'antrian-cru', 'antrian.cru', 'o-inbox-stack', [
                'superadmin' => 'crud', 'cru' => ['read', 'update'], 'administrator' => ['read', 'update'], 'direksi' => ['read'],
            ]],
            ['Antrian Kaji Etik', 'kaji-etik', 'antrian.kepk', 'o-scale', [
                'superadmin' => 'crud', 'kepk' => ['read', 'update'],
            ]],
            ['Antrian Reviewer', 'antrian-reviewer', 'antrian.reviewer', 'o-clipboard-document-check', [
                'superadmin' => 'crud', 'reviewer' => ['read', 'update'],
            ]],
            ['Users', 'users', 'admin.users', 'o-users', [
                'superadmin' => 'crud',
            ]],
            ['Role & Permission', 'roles', 'admin.roles', 'o-key', [
                'superadmin' => 'crud',
            ]],
            ['Manajemen Menu', 'menus', 'admin.menus', 'o-bars-3', [
                'superadmin' => 'crud',
            ]],
            ['Master Survey', 'master-survey', 'admin.survey', 'o-clipboard-document-list', [
                'superadmin' => 'crud', 'administrator' => 'crud',
            ]],
            ['Informasi Kontak', 'informasi-kontak', 'admin.kontak', 'o-phone', [
                'superadmin' => 'crud', 'administrator' => ['read', 'update'],
            ]],
            ['Laporan', 'laporan', 'laporan', 'o-chart-bar', [
                'superadmin' => ['read'], 'direksi' => ['read'], 'cru' => ['read'], 'auditor' => ['read'],
            ]],
            ['Audit Log', 'audit-log', 'audit-log', 'o-finger-print', [
                'superadmin' => ['read'], 'auditor' => ['read'],
            ]],
        ];

        $grants = []; // role => [permission, ...]

        foreach ($menus as $i => [$nama, $slug, $route, $icon, $matrix]) {
            Menu::withTrashed()->firstOrCreate(['slug' => $slug], [
                'nama' => $nama,
                'route' => $route,
                'icon' => $icon,
                'urutan' => ($i + 1) * 10,
                'aktif' => true,
            ]);

            foreach ($matrix as $role => $aksi) {
                $aksi = $aksi === 'crud' ? Menu::AKSI : $aksi;
                foreach ($aksi as $a) {
                    $grants[$role][] = "{$slug}.{$a}";
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($grants as $role => $permissions) {
            Role::findByName($role)->syncPermissions($permissions);
        }
    }
}
