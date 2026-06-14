<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@indoblader.id'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('admin123'),
                'role'     => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'judge@indoblader.id'],
            [
                'name'     => 'Head Judge',
                'password' => Hash::make('judge123'),
                'role'     => 'judge',
            ]
        );
    }
}
