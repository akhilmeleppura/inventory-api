<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if user already exists
        if (!User::where('email', 'admin@example.com')->exists()) {
            User::create([
                'name' => 'System Administrator',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
            ]);
        }
    }
}
