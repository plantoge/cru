<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /** User demo per role — password seragam untuk pengujian. */
    public function run(): void
    {
        $password = 'password';

        foreach (RoleSeeder::ROLES as $role) {
            $user = User::firstOrCreate(
                ['email' => "{$role}@eproposal.test"],
                [
                    'name' => ucwords(str_replace('_', ' ', $role)).' Demo',
                    'username' => $role,
                    'password' => $password,
                    'institusi_asal' => 'RSPI',
                ],
            );

            $user->syncRoles([$role]);
        }
    }
}
