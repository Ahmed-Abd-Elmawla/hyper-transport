<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('admin123'),
        ]);

        // Generate sample data
        $companies = \App\Models\Company::factory(3)->create();

        foreach ($companies as $company) {
            // Drivers & Vehicles for this company
            $drivers = \App\Models\Driver::factory()
                ->count(10)
                ->for($company)
                ->create();

            $vehicles = \App\Models\Vehicle::factory()
                ->count(8)
                ->for($company)
                ->create();

            // Trips for this company with mixed statuses/times
            foreach (range(1, 12) as $i) {
                $driver = $drivers->random();
                $vehicle = $vehicles->random();

                // Create start times around now for realistic overlaps
                $start = \Carbon\Carbon::now()->addHours(fake()->numberBetween(-48, 48));
                $duration = fake()->numberBetween(1, 6);
                $end = (clone $start)->addHours($duration);

                $status = fake()->randomElement(['scheduled', 'in_progress', 'completed']);

                if ($status === 'scheduled') {
                    if ($start->isPast()) {
                        $start = \Carbon\Carbon::now()->addHours(fake()->numberBetween(1, 24));
                    }
                    $endAt = null;
                } elseif ($status === 'in_progress') {
                    if ($start->isFuture()) {
                        $start = \Carbon\Carbon::now()->subHours(fake()->numberBetween(1, 6));
                    }
                    $endAt = null;
                } else {
                    if ($end->isFuture()) {
                        $start = \Carbon\Carbon::now()->subHours($duration + fake()->numberBetween(1, 6));
                        $end = (clone $start)->addHours($duration);
                    }
                    $endAt = $end;
                }

                \App\Models\Trip::create([
                    'company_id' => $company->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicle->id,
                    'destination' => fake()->address(),
                    'start_at' => $start,
                    'end_at' => $endAt,
                    'status' => $status,
                    'notes' => fake()->optional()->sentence(),
                ]);
            }
        }
    }
}
