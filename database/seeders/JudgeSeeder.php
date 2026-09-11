<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class JudgeSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'headjudge@indoblader.id'],
            [
                'name'     => 'Head Judge',
                'username' => 'headjudge',
                'password' => Hash::make('headjudge123'),
                'role'     => 'head_judge',
            ]
        );

        User::firstOrCreate(
            ['email' => 'operator@indoblader.id'],
            [
                'name'     => 'Operator',
                'username' => 'operator',
                'password' => Hash::make('operator123'),
                'role'     => 'operator',
            ]
        );

        $judges = [
            ['name' => 'Judge 1', 'username' => 'judge1', 'email' => 'judge1@indoblader.id'],
            ['name' => 'Judge 2', 'username' => 'judge2', 'email' => 'judge2@indoblader.id'],
            ['name' => 'Judge 3', 'username' => 'judge3', 'email' => 'judge3@indoblader.id'],
        ];

        foreach ($judges as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'username' => $data['username'],
                    'password' => Hash::make('judge123'),
                    'role'     => 'judge',
                ]
            );
        }
    }
}
