<?php

namespace Database\Factories;

use App\Models\WhatsappChat;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhatsappChatFactory extends Factory
{
    protected $model = WhatsappChat::class;

    public function definition(): array
    {
        return [
            'tenant_id'      => \App\Models\Tenant::factory(),
            'wa_id'          => '55' . $this->faker->numerify('119########'),
            'contact_name'   => $this->faker->name(),
            'contact_phone'  => $this->faker->phoneNumber(),
            'status'         => 'open',
            'is_bot_active'  => false,
            'last_message_at'=> now(),
        ];
    }
}
