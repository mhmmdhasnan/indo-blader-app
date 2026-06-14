<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class NasionalRiderSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::where('slug', 'nationals')->firstOrFail();

        $riders = [
            ['slug' => 'iwang',  'name' => 'Iwang Gumilar',   'nick' => 'IWANG',  'city' => 'Bandung',    'age' => 22, 'stance' => 'Regular'],
            ['slug' => 'jessy',  'name' => 'Jessy Pratama',   'nick' => 'JESSY',  'city' => 'Jakarta',    'age' => 20, 'stance' => 'Goofy'],
            ['slug' => 'althaf', 'name' => 'Althaf Ramadhan', 'nick' => 'ALTHAF', 'city' => 'Depok',      'age' => 21, 'stance' => 'Regular'],
            ['slug' => 'zafran', 'name' => 'Zafran Hidayat',  'nick' => 'ZAFRAN', 'city' => 'Bogor',      'age' => 23, 'stance' => 'Regular'],
            ['slug' => 'igan',   'name' => 'Igan Setiawan',   'nick' => 'IGAN',   'city' => 'Bekasi',     'age' => 25, 'stance' => 'Goofy'],
            ['slug' => 'donboo', 'name' => 'Donboo Santoso',  'nick' => 'DONBOO', 'city' => 'Tangerang',  'age' => 24, 'stance' => 'Regular'],
            ['slug' => 'safari', 'name' => 'Safari Nugraha',  'nick' => 'SAFARI', 'city' => 'Yogyakarta', 'age' => 26, 'stance' => 'Regular'],
            ['slug' => 'dedy',   'name' => 'Dedy Kurniawan',  'nick' => 'DEDY',   'city' => 'Surabaya',   'age' => 28, 'stance' => 'Goofy'],
            ['slug' => 'link',   'name' => 'Link Saputra',    'nick' => 'LINK',   'city' => 'Malang',     'age' => 19, 'stance' => 'Regular'],
            ['slug' => 'bimsky', 'name' => 'Bimsky Aditya',   'nick' => 'BIMSKY', 'city' => 'Semarang',   'age' => 22, 'stance' => 'Goofy'],
        ];

        foreach ($riders as $data) {
            $email = $data['slug'] . '@indoblader.id';

            // User
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'     => $data['name'],
                    'password' => Hash::make('password'),
                    'role'     => 'rider',
                ]
            );

            // Rider
            $rider = Rider::firstOrCreate(
                ['slug' => $data['slug']],
                [
                    'user_id'  => $user->id,
                    'name'     => $data['name'],
                    'nick'     => $data['nick'],
                    'city'     => $data['city'],
                    'age'      => $data['age'],
                    'category' => 'STREET',
                    'stance'   => $data['stance'],
                    'points'   => 0,
                    'ig'       => '@' . $data['slug'] . '.blade',
                ]
            );

            // Sync user_id & category on existing rider
            $rider->update(['user_id' => $user->id, 'category' => 'STREET']);

            // Registration
            $reg = Registration::where('event_id', $event->id)
                ->where('name', $rider->name)
                ->first();

            if ($reg) {
                $reg->update([
                    'user_id'  => $user->id,
                    'category' => 'STREET',
                ]);
            } else {
                Registration::create([
                    'user_id'        => $user->id,
                    'entry_code'     => 'IB26-' . strtoupper(Str::random(5)),
                    'name'           => $rider->name,
                    'email'          => $email,
                    'phone'          => '08' . rand(100000000, 999999999),
                    'dob'            => now()->subYears($data['age'])->format('Y-m-d'),
                    'city'           => $data['city'],
                    'stance'         => $data['stance'],
                    'event_id'       => $event->id,
                    'category'       => 'STREET',
                    'experience'     => 'Semi-Pro',
                    'ec_name'        => 'Keluarga ' . explode(' ', $rider->name)[0],
                    'ec_phone'       => '08' . rand(100000000, 999999999),
                    'ec_relation'    => 'Keluarga',
                    'payment_method' => 'Transfer',
                    'payment_status' => 'VERIFIED',
                    'status'         => 'APPROVED',
                ]);
            }
        }
    }
}
