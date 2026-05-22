<?php

namespace Database\Factories;

use App\Models\NgoGrant;
use Illuminate\Database\Eloquent\Factories\Factory;

class NgoGrantFactory extends Factory
{
    protected $model = NgoGrant::class;

    public function definition(): array
    {
        return [
            'tenant_id' => \App\Models\Tenant::factory(),
            'title'     => $this->faker->sentence(4),
            'agency'    => $this->faker->company(),
            'value'     => $this->faker->randomFloat(2, 5000, 100000),
            'status'    => $this->faker->randomElement(['open', 'in_progress', 'approved', 'closed']),
            'deadline'  => $this->faker->dateTimeBetween('now', '+6 months'),
        ];
    }
}
