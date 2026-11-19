<?php

namespace Database\Factories;

use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pengguna>
 */
class PenggunaFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'surel' => fake()->unique()->safeEmail(),
            'kata_sandi' => static::$password ??= Hash::make('password'),
            'peran' => 'staf_produksi',
            'aktif' => true,
            'remember_token' => Str::random(10),
        ];
    }
}
