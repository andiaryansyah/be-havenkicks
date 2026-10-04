<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@havenkicks.com'],
            [
                'name' => 'Admin HavenKicks',
                'password' => 'admin123',
            ]
        );
    }
}