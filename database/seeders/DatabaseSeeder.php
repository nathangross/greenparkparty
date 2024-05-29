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

        User::create([
            'name' => 'nathangross',
            'first_name' => 'Nathan',
            'last_name' => 'Gross',
            'email' => 'nathan@bldg13.com',
            'password' => bcrypt('password'),
        ]);

        Party::create([
            'title' => '2024',
            'primary_date_start' => '2024-06-29 17:00:00',
            'primary_date_end' => '2024-06-29 18:00:00',
        ]);
    }
}
