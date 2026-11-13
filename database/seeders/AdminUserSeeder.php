<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the first super-admin from ADMIN_EMAIL / ADMIN_PASSWORD (and optional ADMIN_NAME).
 * Safe to run on every deploy: an existing account is left alone, so a password changed
 * in the app is never reset back to the env value.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set; skipping admin user.');

            return;
        }

        $user = User::firstOrCreate(
            ['email' => strtolower(trim($email))],
            [
                'name' => env('ADMIN_NAME', 'Admin'),
                'password' => $password, // hashed by the model's 'hashed' cast
                'role' => 'admin',
                'is_super_admin' => true,
                'is_active' => true,
            ]
        );

        if (! $user->wasRecentlyCreated) {
            $this->command?->info("Admin {$user->email} already exists; left unchanged.");

            return;
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        $this->command?->info("Admin {$user->email} created.");
    }
}
