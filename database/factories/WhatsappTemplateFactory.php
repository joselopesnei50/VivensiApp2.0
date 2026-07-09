<?php

namespace Database\Factories;

use App\Models\WhatsappTemplate;
use App\Models\WhatsappInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhatsappTemplateFactory extends Factory
{
    protected $model = WhatsappTemplate::class;

    public function definition(): array
    {
        $instance = WhatsappInstance::factory()->cloudApi()->create();

        return [
            'tenant_id'            => $instance->tenant_id,
            'whatsapp_instance_id' => $instance->id,
            'waba_id'              => $instance->waba_id,
            'meta_template_id'     => (string) random_int(100000000000, 999999999999),
            'name'                 => 'test_tpl_' . strtolower($this->faker->lexify('??????')),
            'language'             => 'pt_BR',
            'category'             => WhatsappTemplate::CATEGORY_UTILITY,
            'status'               => WhatsappTemplate::STATUS_APPROVED,
            'components'           => [
                ['type' => 'BODY', 'text' => 'Olá {{1}}, teste do template.'],
            ],
            'synced_at'            => now(),
        ];
    }
}
