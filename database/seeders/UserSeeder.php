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

        // Reviewer kedua — uji penugasan multi-reviewer oleh KEPK
        $r2 = User::firstOrCreate(
            ['email' => 'reviewer2@eproposal.test'],
            ['name' => 'Reviewer Demo 2', 'username' => 'reviewer2', 'password' => $password, 'institusi_asal' => 'RSPI'],
        );
        $r2->syncRoles(['reviewer']);
    }
}
