<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'leave_type' => $this->faker->randomElement(['annual', 'sick', 'maternity', 'paternity', 'unpaid', 'unauthorized_absence']),
            'start_date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'end_date' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'total_days' => $this->faker->numberBetween(1, 10),
        ];
    }
}
