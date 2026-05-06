<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Party;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::updateOrCreate(
            ['email' => 'nathan@bldg13.com'],
            [
                'name' => 'nathangross',
                'first_name' => 'Nathan',
                'last_name' => 'Gross',
                'email' => 'nathan@bldg13.com',
                'is_organizer' => true,
                'password' => bcrypt(env('ADMIN_PASSWORD')),
            ],
        );

        Party::query()->update(['is_active' => false]);

        Party::updateOrCreate(
            ['title' => '2026'],
            [
                'is_active' => true,
                'primary_date_start' => '2026-06-27 16:00:00',
                'primary_date_end' => '2026-06-27 20:00:00',
                'secondary_date_start' => '2026-06-28 16:00:00',
                'secondary_date_end' => '2026-06-28 20:00:00',
            ],
        );
    }
}
