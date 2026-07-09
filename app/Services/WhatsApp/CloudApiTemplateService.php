<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsappInstance;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * CRUD de templates WhatsApp Cloud API contra Graph API + cache local.
 *
 * Convenção de multi-tenancy: TODAS as operações recebem $instance
 * (uma WhatsappInstance de um tenant). Usa o graph_access_token DAQUELA instância —
 * nunca credenciais globais. Isolamento garantido no service layer.
 */
class CloudApiTemplateService
{
    private string $apiVersion;

    public function __construct()
    {
        $this->apiVersion = config('whatsapp.meta_api_version', 'v22.0');
    }

    /**
     * Puxa TODOS os templates da WABA da instância e sincroniza com o cache local.
     * Idempotente — updateOrCreate por (instance_id, name, language).
     *
     * @return int Total sincronizados
     */
    public function syncFromMeta(WhatsappInstance $instance): int
    {
        $this->assertCloudApi($instance);

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$instance->waba_id}/message_templates";

        $response = Http::withToken($instance->graph_access_token)
            ->get($url, [
                'limit'  => 200,
                'fields' => 'id,name,status,category,language,components,rejected_reason',
            ]);

        if ($response->failed()) {
            Log::error('CloudApi Templates: sync falhou', [
                'instance_id' => $instance->id,
                'body'        => $response->body(),
            ]);
            throw new RuntimeException('Falha ao sincronizar templates da Meta.');
        }

        $rows = $response->json('data', []);
        $count = 0;

        foreach ($rows as $row) {
            WhatsappTemplate::withoutGlobalScope('tenant')->updateOrCreate(
                [
                    'whatsapp_instance_id' => $instance->id,
                    'name'                 => $row['name'],
                    'language'             => $row['language'],
                ],
                [
                    'tenant_id'         => $instance->tenant_id,
                    'waba_id'           => $instance->waba_id,
                    'meta_template_id'  => $row['id']  ?? null,
                    'category'          => $row['category'] ?? WhatsappTemplate::CATEGORY_UTILITY,
                    'status'            => $row['status'] ?? WhatsappTemplate::STATUS_PENDING,
                    'rejection_reason'  => $row['rejected_reason'] ?? null,
                    'components'        => $row['components'] ?? [],
                    'synced_at'         => now(),
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Cria template na Meta e persiste local com status PENDING.
     * Meta responde com id + status. Aprovação real chega via webhook depois.
     *
     * @param  array $data ['name', 'language', 'category', 'components']
     */
    public function create(WhatsappInstance $instance, array $data): WhatsappTemplate
    {
        $this->assertCloudApi($instance);

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$instance->waba_id}/message_templates";

        $response = Http::withToken($instance->graph_access_token)
            ->post($url, [
                'name'       => $data['name'],
                'language'   => $data['language'],
                'category'   => $data['category'],
                'components' => $data['components'],
            ]);

        if ($response->failed()) {
            Log::error('CloudApi Templates: create falhou', [
                'instance_id' => $instance->id,
                'name'        => $data['name'],
                'body'        => $response->body(),
            ]);

            $err = $response->json('error.message') ?? 'Erro desconhecido';
            throw new RuntimeException("Meta rejeitou o template: {$err}");
        }

        return WhatsappTemplate::withoutGlobalScope('tenant')->updateOrCreate(
            [
                'whatsapp_instance_id' => $instance->id,
                'name'                 => $data['name'],
                'language'             => $data['language'],
            ],
            [
                'tenant_id'         => $instance->tenant_id,
                'waba_id'           => $instance->waba_id,
                'meta_template_id'  => $response->json('id'),
                'category'          => $data['category'],
                'status'            => $response->json('status', WhatsappTemplate::STATUS_PENDING),
                'components'        => $data['components'],
                'synced_at'         => now(),
            ]
        );
    }

    /**
     * Deleta template na Meta e apaga do cache local.
     */
    public function delete(WhatsappInstance $instance, WhatsappTemplate $template): bool
    {
        $this->assertCloudApi($instance);
        $this->assertSameTenant($instance, $template);

        // Meta permite delete por name (afeta TODAS as línguas do template) ou by id.
        // Usamos name+language pra ser cirurgico com uma variante.
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$instance->waba_id}/message_templates";

        $response = Http::withToken($instance->graph_access_token)
            ->delete($url, [
                'hsm_id' => $template->meta_template_id,
                'name'   => $template->name,
            ]);

        if ($response->failed()) {
            Log::error('CloudApi Templates: delete falhou', [
                'template_id' => $template->id,
                'body'        => $response->body(),
            ]);
            return false;
        }

        $template->delete();
        return true;
    }

    /**
     * Atualiza status/rejection_reason de um template a partir de webhook.
     * Chamado pelo ProcessCloudApiWebhook quando recebe message_template_status_update.
     */
    public function applyStatusUpdate(string $wabaId, string $metaTemplateId, string $newStatus, ?string $rejectionReason = null): ?WhatsappTemplate
    {
        $template = WhatsappTemplate::withoutGlobalScope('tenant')
            ->where('waba_id', $wabaId)
            ->where('meta_template_id', $metaTemplateId)
            ->first();

        if (!$template) {
            Log::info('CloudApi Templates: status update pra template desconhecido', [
                'waba_id'          => $wabaId,
                'meta_template_id' => $metaTemplateId,
            ]);
            return null;
        }

        $template->update([
            'status'           => $newStatus,
            'rejection_reason' => $rejectionReason,
            'synced_at'        => now(),
        ]);

        return $template;
    }

    private function assertCloudApi(WhatsappInstance $instance): void
    {
        if (!$instance->isCloudApi()) {
            throw new RuntimeException("Instância {$instance->id} não é Cloud API — templates gerenciados só pra Cloud.");
        }
        if (empty($instance->waba_id) || empty($instance->graph_access_token)) {
            throw new RuntimeException("Instância {$instance->id} sem waba_id ou access_token configurado.");
        }
    }

    private function assertSameTenant(WhatsappInstance $instance, WhatsappTemplate $template): void
    {
        if ((int) $instance->tenant_id !== (int) $template->tenant_id) {
            throw new RuntimeException('Instância e template pertencem a tenants diferentes — operação bloqueada.');
        }
    }
}
