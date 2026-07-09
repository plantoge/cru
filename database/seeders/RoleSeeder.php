<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /** 9 aktor prd §1. */
    public const ROLES = [
        'superadmin',
        'direksi',
        'cru',
        'administrator',
        'peneliti',
        'reviewer',
        'kepk',
        'rekam_medis',
        'auditor',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::findOrCreate($role);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
