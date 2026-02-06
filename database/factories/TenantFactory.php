<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'document' => $this->faker->unique()->numerify('##############'), // CPF/CNPJ
            'type' => $this->faker->randomElement(['ngo', 'manager', 'common']),
            'subscription_status' => 'active',
            'trial_ends_at' => now()->addDays(7),
        ];
    }

    /**
     * Indicate that the tenant has an expired trial.
     */
    public function trialExpired()
    {
        return $this->state(function (array $attributes) {
            return [
                'trial_ends_at' => now()->subDays(1),
                'subscription_status' => 'trial_expired',
            ];
        });
    }

    /**
     * Indicate that the tenant subscription is inactive.
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'subscription_status' => 'inactive',
            ];
        });
    }

    /**
     * Indicate that the tenant is an NGO type.
     */
    public function ngo()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'ngo',
                'name' => 'ONG ' . fake()->words(3, true),
            ];
        });
    }

    /**
     * Indicate that the tenant is a project manager type.
     */
    public function manager()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'manager',
            ];
        });
    }
}
