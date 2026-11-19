<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Pengguna::factory()->create([
            'nama' => 'Test User',
            'surel' => 'test@example.com',
            'kata_sandi' => bcrypt('password'),
            'peran' => 'owner',
            'aktif' => true,
        ]);
    }
}
