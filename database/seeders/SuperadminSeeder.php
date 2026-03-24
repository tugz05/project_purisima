<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a superadmin user compatible with the users table schema:
 *
 * @see database/migrations/0001_01_01_000000_create_users_table.php (name, email, password, role, timestamps)
 * @see database/migrations/2025_09_11_000100_add_resident_fields_to_users_table.php (optional columns with defaults)
 */
class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'superadmin@example.com';

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Superadmin User',
                'password' => Hash::make('password'),
                'role' => 'superadmin',
            ],
        );

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();
    }
}
