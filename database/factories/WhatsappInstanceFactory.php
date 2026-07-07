<?php

namespace Database\Factories;

use App\Models\WhatsappInstance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WhatsappInstanceFactory extends Factory
{
    protected $model = WhatsappInstance::class;

    public function definition(): array
    {
        return [
            'tenant_id'      => \App\Models\Tenant::factory(),
            'provider'       => WhatsappInstance::PROVIDER_EVOLUTION,
            'instance_name'  => 'vivensi_test_' . Str::random(6),
            'instance_token' => Str::random(64),
            'status'         => 'open',
            'phone_number'   => null,
            'daily_limit'    => 300,
        ];
    }

    public function cloudApi(): self
    {
        return $this->state(fn () => [
            'provider'           => WhatsappInstance::PROVIDER_CLOUD_API,
            'waba_id'            => '10' . random_int(1000000000, 9999999999),
            'phone_number_id'    => '20' . random_int(1000000000, 9999999999),
            'graph_access_token' => 'EAAG' . Str::random(200),
        ]);
    }
}
