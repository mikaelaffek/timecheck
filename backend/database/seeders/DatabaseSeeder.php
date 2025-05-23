<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            LocationSeeder::class,
            OvertimeRuleSeeder::class,
            ScheduleSeeder::class,
            TimeRegistrationSeeder::class,
        ]);
    }
}
