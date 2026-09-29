<?php

namespace Database\Seeders;

use App\Models\User;
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
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        if (!User::where('email', 'admin@motiro.test')->exists()) {
            User::factory()->create([
                'name' => 'Admin User',
                'username' => 'admin',
                'email' => 'admin@motiro.test',
            ]);
        }
    }
}
