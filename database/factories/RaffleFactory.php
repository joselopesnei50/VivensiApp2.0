<?php

namespace Database\Factories;

use App\Models\Raffle;
use Illuminate\Database\Eloquent\Factories\Factory;

class RaffleFactory extends Factory
{
    protected $model = Raffle::class;

    public function definition(): array
    {
        return [
            'tenant_id'     => \App\Models\Tenant::factory(),
            'title'         => $this->faker->sentence(3),
            'description'   => $this->faker->paragraph(),
            'ticket_price'  => $this->faker->randomFloat(2, 5, 50),
            'total_tickets' => $this->faker->numberBetween(50, 500),
            'draw_date'     => $this->faker->dateTimeBetween('+1 week', '+3 months'),
            'status'        => 'active',
        ];
    }
}
