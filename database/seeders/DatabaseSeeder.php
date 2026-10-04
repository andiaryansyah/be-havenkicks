<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat ADMIN
        User::create([
            'name' => 'Admin HavenKicks',
            'email' => 'admin@havenkicks.com',
            'password' => 'admin123',
            'role' => 'admin'
        ]);

        // 2. Buat USER Pembeli
        User::create([
            'name' => 'customer',
            'email' => 'customer@gmail.com',
            'password' => 'user12345',
            'role' => 'user'
        ]);

        // 3. (Opsional) Buat Categories Default biar tidak input manual
        \App\Models\Category::create(['name' => 'Nike']);
        \App\Models\Category::create(['name' => 'Adidas']);
        \App\Models\Category::create(['name' => 'New Balance']);
    }
}