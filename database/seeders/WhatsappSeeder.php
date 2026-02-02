<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappConfig;
use App\Models\WhatsappChat;
use App\Models\WhatsappMessage;
use App\Models\WhatsappNote;
use App\Models\CannedResponse;

class WhatsappSeeder extends Seeder
{
    public function run()
    {
        // 1. Get User and Tenant
        $user = User::first();
        if (!$user) {
            $this->command->error("Nenhum usuário encontrado. Execute o seeder de usuários primeiro.");
            return;
        }
        $tenantId = $user->tenant_id;

        // 2. Clear previous data (optional, but good for reset)
        // WhatsappChat::where('tenant_id', $tenantId)->delete(); // Careful with production
        // CannedResponse::where('tenant_id', $tenantId)->delete(); 

        // 3. Create Config
        WhatsappConfig::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'instance_id' => 'TEST_INSTANCE',
                'token' => 'TEST_TOKEN',
                'client_token' => 'TEST_CLIENT',
                'is_active' => true,
                'ai_enabled' => true,
                'ai_training' => "Você é o Bruce AI, assistente virtual avançado da Vivensi. Seja gentil, profissional e altamente eficiente. Use emojis moderadamente."
            ]
        );

        // 4. Create Canned Responses
        $macros = [
            ['title' => '/saudacao', 'content' => 'Olá! Eu sou o Bruce AI, seu assistente virtual. Como posso ajudar você a escalar seus resultados hoje?'],
            ['title' => '/pix', 'content' => 'Para pagamentos via PIX, utilize nossa chave CNPJ: 12.345.678/0001-90 (Banco Itaú).'],
            ['title' => '/aguarde', 'content' => 'Estou processando sua solicitação com meus algoritmos... Só um momento, por favor.'],
            ['title' => '/fechamento', 'content' => 'Foi um prazer ajudar! O Bruce AI está sempre à disposição para otimizar seu dia.']
        ];

        foreach ($macros as $m) {
            CannedResponse::updateOrCreate(
                ['tenant_id' => $tenantId, 'title' => $m['title']],
                ['content' => $m['content']]
            );
        }

        // 5. Create Chats & Messages
        $contacts = [
            ['name' => 'Maria Silva', 'phone' => '5511999991111', 'status' => 'open'],
            ['name' => 'João Souza', 'phone' => '5511988882222', 'status' => 'open'],
            ['name' => 'Empresa X (Carlos)', 'phone' => '5511977773333', 'status' => 'waiting'],
        ];

        foreach ($contacts as $index => $c) {
            $chat = WhatsappChat::firstOrCreate(
                ['tenant_id' => $tenantId, 'wa_id' => $c['phone']],
                [
                    'contact_name' => $c['name'],
                    'contact_phone' => $c['phone'],
                    'status' => $c['status'],
                    'last_message_at' => now()->subMinutes($index * 15)
                ]
            );

            // History
            WhatsappMessage::firstOrCreate([
                'chat_id' => $chat->id,
                'content' => 'Olá, gostaria de saber mais sobre os planos.',
            ], [
                'message_id' => 'MSG_' . uniqid(),
                'direction' => 'inbound',
                'type' => 'text',
                'created_at' => now()->subMinutes(60)
            ]);

            WhatsappMessage::firstOrCreate([
                'chat_id' => $chat->id,
                'content' => 'Claro! Temos planos para ONGs e Gestores. Qual o seu perfil?',
            ], [
                'message_id' => 'MSG_' . uniqid(),
                'direction' => 'outbound',
                'type' => 'text',
                'created_at' => now()->subMinutes(59)
            ]);

            // Add Note
            WhatsappNote::firstOrCreate([
                'chat_id' => $chat->id,
                'content' => 'Cliente demonstrou interesse no plano Enterprise.',
            ], [
                'user_id' => $user->id,
                'type' => 'manual',
                'created_at' => now()->subMinutes(30)
            ]);
            
            // Add AI Insight Note
            WhatsappNote::firstOrCreate([
                'chat_id' => $chat->id,
                'type' => 'ai_insight',
            ], [
                'user_id' => null, // AI/System
                'content' => 'Sentimento do cliente: Positivo. Probabilidade de fechamento: Alta.',
                'created_at' => now()->subMinutes(29)
            ]);
        }

        $this->command->info("WhatsApp Mock Data Seeded Successfully with Bruce AI!");
    }
}
