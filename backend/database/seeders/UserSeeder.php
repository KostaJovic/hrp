<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'kosta.j@icloud.com'],
            ['name' => 'Kosta', 'password' => 'password'],
        );
    }
}
