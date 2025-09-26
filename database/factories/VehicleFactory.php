<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Company;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        $brands = ['Toyota', 'Ford', 'Chevrolet', 'Nissan', 'Hyundai', 'Kia', 'Volkswagen'];
        $models = ['Sedan', 'SUV', 'Pickup', 'Van', 'Hatchback', 'Minibus'];

        return [
            'company_id' => Company::factory(),
            'brand' => $this->faker->randomElement($brands),
            'model' => $this->faker->randomElement($models),
            'year' => (int) $this->faker->numberBetween(2010, (int) date('Y')),
            'color' => $this->faker->hexColor(),
            'plate_number' => strtoupper($this->faker->unique()->bothify('??-####-??')),
            'capacity' => $this->faker->numberBetween(2, 50),
            'status' => 'available',
            'is_active' => true,
        ];
    }
}