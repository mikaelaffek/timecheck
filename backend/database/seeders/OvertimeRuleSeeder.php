<?php

namespace Database\Seeders;

use App\Models\OvertimeRule;
use Illuminate\Database\Seeder;

class OvertimeRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $overtimeRules = [
            [
                'name' => 'Weekday Evening',
                'type' => 'weekday_evening',
                'multiplier' => 1.5,
                'description' => 'Applies to hours worked after 18:00 on weekdays',
                'is_active' => true,
            ],
            [
                'name' => 'Weekend',
                'type' => 'weekend',
                'multiplier' => 2.0,
                'description' => 'Applies to all hours worked on Saturday and Sunday',
                'is_active' => true,
            ],
            [
                'name' => 'Holiday',
                'type' => 'holiday',
                'multiplier' => 2.5,
                'description' => 'Applies to all hours worked on public holidays',
                'is_active' => true,
            ],
            [
                'name' => 'Night Shift',
                'type' => 'night_shift',
                'multiplier' => 1.75,
                'description' => 'Applies to hours worked between 22:00 and 06:00',
                'is_active' => true,
            ],
        ];
        
        foreach ($overtimeRules as $ruleData) {
            OvertimeRule::create($ruleData);
        }
    }
}
