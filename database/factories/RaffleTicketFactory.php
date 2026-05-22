<?php

namespace Database\Factories;

use App\Models\RaffleTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

class RaffleTicketFactory extends Factory
{
    protected $model = RaffleTicket::class;

    public function definition(): array
    {
        return [
            'raffle_id'   => \App\Models\Raffle::factory(),
            'number'      => $this->faker->unique()->numberBetween(1, 9999),
            'status'      => 'available',
            'buyer_name'  => null,
            'buyer_email' => null,
            'buyer_phone' => null,
            'reserved_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function paid(): static
    {
        return $this->state([
            'status'      => 'paid',
            'buyer_name'  => $this->faker->name(),
            'buyer_email' => $this->faker->email(),
            'buyer_phone' => $this->faker->phoneNumber(),
        ]);
    }

    public function reserved(): static
    {
        return $this->state(['status' => 'pending']);
    }
}
