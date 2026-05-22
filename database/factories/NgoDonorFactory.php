<?php

namespace Database\Factories;

use App\Models\NgoDonor;
use Illuminate\Database\Eloquent\Factories\Factory;

class NgoDonorFactory extends Factory
{
    protected $model = NgoDonor::class;

    public function definition(): array
    {
        return [
            'tenant_id' => \App\Models\Tenant::factory(),
            'name'      => $this->faker->company(),
            'email'     => $this->faker->unique()->safeEmail(),
            'phone'     => $this->faker->phoneNumber(),
            'type'      => $this->faker->randomElement(['individual', 'company', 'government']),
        ];
    }
}
