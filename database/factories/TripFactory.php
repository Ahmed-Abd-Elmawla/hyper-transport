<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trip>
 */
class TripFactory extends Factory
{
    public function definition(): array
    {
        // Default times near "now" to create meaningful overlaps/non-overlaps
        $start = Carbon::now()->addHours($this->faker->numberBetween(-48, 48));
        $durationHours = $this->faker->numberBetween(1, 6);
        $end = (clone $start)->addHours($durationHours);

        $status = $this->faker->randomElement(['scheduled', 'in_progress', 'completed']);

        // scheduled: future start; in_progress: started and no end; completed: has end
        if ($status === 'scheduled') {
            if ($start->isPast()) {
                $start = Carbon::now()->addHours($this->faker->numberBetween(1, 24));
            }
            $endAt = null;
        } elseif ($status === 'in_progress') {
            if ($start->isFuture()) {
                $start = Carbon::now()->subHours($this->faker->numberBetween(1, 6));
            }
            $endAt = null;
        } else { // completed
            if ($end->isFuture()) {
                $start = Carbon::now()->subHours($durationHours + $this->faker->numberBetween(1, 6));
                $end = (clone $start)->addHours($durationHours);
            }
            $endAt = $end;
        }

        return [
            'company_id' => Company::factory(),
            'driver_id' => Driver::factory(),
            'vehicle_id' => Vehicle::factory(),
            'destination' => $this->faker->address(),
            'start_at' => $start,
            'end_at' => $endAt,
            'status' => $status,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}