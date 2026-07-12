<?php

namespace Database\Factories;

use App\Models\WhatsappConversation;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhatsappConversationFactory extends Factory
{
    protected $model = WhatsappConversation::class;

    public function definition(): array
    {
        return [
            'tenant_id'            => \App\Models\Tenant::factory(),
            'whatsapp_instance_id' => \App\Models\WhatsappInstance::factory()->cloudApi(),
            'meta_conversation_id' => 'conv_' . fake()->uuid(),
            'contact_wa_id'        => '55' . fake()->numerify('###########'),
            'category'             => WhatsappConversation::CATEGORY_UTILITY,
            'origin_type'          => 'utility',
            'expires_at'           => now()->addDay(),
            'started_at'           => now(),
            'cost_usd_micros'      => 8000,
            'pricing_model'        => WhatsappConversation::PRICING_MODEL_CBP,
            'is_billable'          => true,
            'country_code'         => 'BR',
        ];
    }
}
