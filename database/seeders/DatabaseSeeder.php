<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'info@arsus.nl'],
            [
                'name' => 'Arsus',
                'password' => Hash::make('arsus@29'),
                'role' => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'ibbiblingua@gmail.com'],
            [
                'name' => 'Ibbi',
                'password' => Hash::make('pacik@276'),
                'role' => 'admin',
            ]
        );
    }
}
