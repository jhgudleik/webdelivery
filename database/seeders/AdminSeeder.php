<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@webdelivery.com'],
            [
                'name'     => 'Администратор',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
            ]
        );
    }
}
