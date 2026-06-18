<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\WhatsappForm;
use Illuminate\Database\Seeder;

/**
 * Seeder demonstrativo do formulário conversacional (Fase 4 — item 2.5).
 *
 * Cria um formulário 'Cadastro Apoiador' com 3 perguntas (text, number, yes_no)
 * no primeiro tenant ativo encontrado. Reusável: se já existe form com esse
 * nome, ignora.
 *
 * Uso:
 *   php artisan db:seed --class=DemoFormSeeder
 *
 * Para tenant específico:
 *   TENANT_ID=42 php artisan db:seed --class=DemoFormSeeder
 */
class DemoFormSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) (getenv('TENANT_ID') ?: 0);
        if ($tenantId <= 0) {
            $tenant = Tenant::orderBy('id')->first();
            if ($tenant === null) {
                $this->command->error('Nenhum tenant encontrado. Crie um tenant antes.');
                return;
            }
            $tenantId = (int) $tenant->id;
        }

        $existing = WhatsappForm::where('tenant_id', $tenantId)
            ->where('name', 'Cadastro Apoiador')
            ->first();

        if ($existing) {
            $this->command->warn("Form 'Cadastro Apoiador' já existe (id={$existing->id}, tenant_id={$tenantId}). Nada a fazer.");
            return;
        }

        $form = WhatsappForm::create([
            'tenant_id'  => $tenantId,
            'name'       => 'Cadastro Apoiador',
            'description'=> 'Formulário demo para testar a Fase 4 (2.5).',
            'is_active'  => true,
        ]);

        $form->questions()->create([
            'position'  => 1,
            'field_key' => 'nome',
            'text'      => 'Qual seu nome?',
            'type'      => 'text',
            'required'  => true,
        ]);

        $form->questions()->create([
            'position'  => 2,
            'field_key' => 'idade',
            'text'      => 'Qual sua idade?',
            'type'      => 'number',
            'required'  => true,
            'min_value' => 18,
            'max_value' => 100,
        ]);

        $form->questions()->create([
            'position'  => 3,
            'field_key' => 'apoia',
            'text'      => 'Confirma seu apoio à campanha?',
            'type'      => 'yes_no',
            'required'  => true,
        ]);

        $this->command->info("Form criado: id={$form->id}, tenant_id={$tenantId}, 3 perguntas.");
    }
}
