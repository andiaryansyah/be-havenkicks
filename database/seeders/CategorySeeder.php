<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    $sport = \App\Models\Category::create(['name' => 'Sport']);
    $casual = \App\Models\Category::create(['name' => 'Casual']);
    
    \App\Models\Category::create(['name' => 'Running', 'parent_id' => $sport->id]);
    \App\Models\Category::create(['name' => 'Lifestyle', 'parent_id' => $sport->id]);
    \App\Models\Category::create(['name' => 'Classic', 'parent_id' => $casual->id]);
}
}
