<?php

namespace App\Http\Controllers;

use App\Models\WhatsappInstance;
use App\Models\WhatsappTemplate;
use App\Services\WhatsApp\CloudApiTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * CRUD de templates WhatsApp Cloud API por tenant.
 *
 * Todo escopo é filtrado por tenant do usuário logado. Instâncias Evolution
 * são ignoradas (rota é exclusiva Cloud API — Evolution usa texto livre sem template).
 */
class WhatsappTemplateController extends Controller
{
    public function __construct(private CloudApiTemplateService $templates) {}

    /**
     * Lista todos os templates das instâncias Cloud do tenant.
     */
    public function index()
    {
        Gate::authorize('access-whatsapp');

        $tenantId = auth()->user()->tenant_id;

        $instances = WhatsappInstance::where('tenant_id', $tenantId)
            ->where('provider', WhatsappInstance::PROVIDER_CLOUD_API)
            ->get();

        $templates = WhatsappTemplate::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->get();

        return view('whatsapp.templates.index', compact('instances', 'templates'));
    }

    /**
     * Form de criação.
     */
    public function create()
    {
        Gate::authorize('access-whatsapp');

        $instances = WhatsappInstance::where('tenant_id', auth()->user()->tenant_id)
            ->where('provider', WhatsappInstance::PROVIDER_CLOUD_API)
            ->get(['id', 'instance_name', 'phone_number', 'waba_id']);

        if ($instances->isEmpty()) {
            return redirect()->route('whatsapp.templates.cloud.index')
                ->with('error', 'Você precisa ter pelo menos uma instância Cloud API conectada.');
        }

        return view('whatsapp.templates.create', compact('instances'));
    }

    /**
     * Cria template na Meta + persiste local.
     */
    public function store(Request $request)
    {
        Gate::authorize('access-whatsapp');

        $data = $request->validate([
            'whatsapp_instance_id' => ['required', 'integer'],
            'name'                 => ['required', 'string', 'regex:/^[a-z0-9_]+$/', 'max:512'],
            'language'             => ['required', 'string', 'max:20'],
            'category'             => ['required', 'in:MARKETING,UTILITY,AUTHENTICATION'],
            'body'                 => ['required', 'string', 'max:1024'],
            'footer'               => ['nullable', 'string', 'max:60'],
        ], [
            'name.regex' => 'Use apenas letras minúsculas, números e underscore (ex: order_confirmation).',
        ]);

        $instance = WhatsappInstance::where('tenant_id', auth()->user()->tenant_id)
            ->where('id', $data['whatsapp_instance_id'])
            ->firstOrFail();

        // Componentes no formato Meta
        $components = [
            ['type' => 'BODY', 'text' => $data['body']],
        ];
        if (!empty($data['footer'])) {
            $components[] = ['type' => 'FOOTER', 'text' => $data['footer']];
        }

        try {
            $template = $this->templates->create($instance, [
                'name'       => $data['name'],
                'language'   => $data['language'],
                'category'   => $data['category'],
                'components' => $components,
            ]);
        } catch (Throwable $e) {
            Log::warning('WhatsappTemplateController.store falhou', [
                'tenant_id' => auth()->user()->tenant_id,
                'error'     => $e->getMessage(),
            ]);
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('whatsapp.templates.cloud.index')
            ->with('success', "Template \"{$template->name}\" enviado para aprovação da Meta.");
    }

    /**
     * Delete template.
     */
    public function destroy(WhatsappTemplate $template)
    {
        Gate::authorize('access-whatsapp');

        // Isolamento explícito por tenant — evita IDOR
        if ((int) $template->tenant_id !== (int) auth()->user()->tenant_id) {
            abort(404);
        }

        try {
            $this->templates->delete($template->instance, $template);
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao deletar: ' . $e->getMessage());
        }

        return redirect()->route('whatsapp.templates.cloud.index')
            ->with('success', "Template \"{$template->name}\" removido.");
    }

    /**
     * Sincroniza templates da Meta para uma instância.
     */
    public function sync(Request $request)
    {
        Gate::authorize('access-whatsapp');

        $data = $request->validate([
            'whatsapp_instance_id' => ['required', 'integer'],
        ]);

        $instance = WhatsappInstance::where('tenant_id', auth()->user()->tenant_id)
            ->where('id', $data['whatsapp_instance_id'])
            ->firstOrFail();

        try {
            $count = $this->templates->syncFromMeta($instance);
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao sincronizar: ' . $e->getMessage());
        }

        return back()->with('success', "{$count} template(s) sincronizado(s) da Meta.");
    }
}
