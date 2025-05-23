<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Main Office',
                'address' => 'Storgatan 1, 111 23 Stockholm',
                'latitude' => 59.329323,
                'longitude' => 18.068581,
            ],
            [
                'name' => 'North Branch',
                'address' => 'Kungsgatan 65, 753 21 Uppsala',
                'latitude' => 59.858562,
                'longitude' => 17.638927,
            ],
            [
                'name' => 'South Branch',
                'address' => 'Stortorget 2, 211 34 Malmö',
                'latitude' => 55.605866,
                'longitude' => 13.000224,
            ],
            [
                'name' => 'West Branch',
                'address' => 'Kungsportsavenyen 25, 411 36 Göteborg',
                'latitude' => 57.698814,
                'longitude' => 11.975370,
            ],
        ];
        
        foreach ($locations as $locationData) {
            Location::create($locationData);
        }
    }
}
