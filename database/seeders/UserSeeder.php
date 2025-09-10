<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
            [
                'name' => 'Staff User',
                'email' => 'staff@example.com',
                'password' => Hash::make('password'),
                'role' => 'staff',
            ],
            [
                'name' => 'Enforcer User',
                'email' => 'enforcer@example.com',
                'password' => Hash::make('password'),
                'role' => 'enforcer',
            ],
            [
                'name' => 'Resident User',
                'email' => 'resident@example.com',
                'password' => Hash::make('password'),
                'role' => 'resident',
            ],
        ]);
    }
}
