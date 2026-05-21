<?php

namespace Database\Factories;

use App\Models\WhatsappConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhatsappConfigFactory extends Factory
{
    protected $model = WhatsappConfig::class;

    public function definition(): array
    {
        return [
            'tenant_id'                       => \App\Models\Tenant::factory(),
            'is_active'                       => true,
            'ai_enabled'                      => false,
            'outbound_enabled'                => true,
            'require_opt_in'                  => false,
            'enforce_24h_window'              => false,
            'allow_templates_outside_window'  => true,
            'max_outbound_per_minute'         => 20,
            'min_outbound_delay_seconds'      => 1,
        ];
    }
}
