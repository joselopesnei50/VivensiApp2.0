<?php

namespace App\Http\Controllers;

use App\Models\WhatsappInstance;
use App\Models\WhatsappTemplate;
use App\Services\WhatsApp\CloudApiTemplateService;
use App\Services\WhatsApp\WhatsAppSenderFactory;
use Illuminate\Http\JsonResponse;
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
            'variable_samples'     => ['nullable', 'array'],
            'variable_samples.*'   => ['nullable', 'string', 'max:255'],
        ], [
            'name.regex' => 'Use apenas letras minúsculas, números e underscore (ex: order_confirmation).',
        ]);

        $instance = WhatsappInstance::where('tenant_id', auth()->user()->tenant_id)
            ->where('id', $data['whatsapp_instance_id'])
            ->firstOrFail();

        // Regras Meta especificas de variaveis + amostras (bloqueia antes de
        // chamar a Graph API pra nao gastar rate limit em template que ja sabemos
        // que sera rejeitado).
        $samples   = $data['variable_samples'] ?? [];
        $variables = $this->templates->extractVariables($data['body']);
        $errors    = $this->templates->validateBodyAndSamples($data['body'], $variables, $samples);

        if (!empty($errors)) {
            return back()->withInput()->withErrors($errors);
        }

        // Componentes no formato Meta — buildBodyComponent injeta example.body_text
        // quando ha variaveis (obrigatorio pela Meta) e omite quando nao ha
        // (passar example em corpo sem variavel tambem causa erro).
        $components = [$this->templates->buildBodyComponent($data['body'], $samples)];
        if (!empty($data['footer'])) {
            $components[] = ['type' => 'FOOTER', 'text' => $data['footer']];
        }

        try {
            $template = $this->templates->create($instance, [
                'name'             => $data['name'],
                'language'         => $data['language'],
                'category'         => $data['category'],
                'components'       => $components,
                'variable_samples' => !empty($variables) ? $samples : null,
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

    /**
     * Envia mensagem de teste usando o template aprovado.
     *
     * Multi-tenancy: template precisa pertencer ao tenant do usuário logado.
     * Só aceita templates APPROVED (isSendable). Retorna JSON com resultado bruto
     * do provider (WhatsAppSenderFactory).
     */
    public function sendTest(Request $request, WhatsappTemplate $template): JsonResponse
    {
        Gate::authorize('access-whatsapp');

        // Isolamento explícito por tenant (IDOR)
        if ((int) $template->tenant_id !== (int) auth()->user()->tenant_id) {
            return response()->json(['ok' => false, 'error' => 'Template não encontrado.'], 404);
        }

        if (!$template->isSendable()) {
            return response()->json([
                'ok'    => false,
                'error' => "Template com status \"{$template->status}\" não pode enviar. Só APPROVED.",
            ], 422);
        }

        $data = $request->validate([
            'to'        => ['required', 'string', 'regex:/^\+?\d{10,15}$/'],
            'variables' => ['nullable', 'array', 'max:20'],
            'variables.*' => ['string', 'max:200'],
        ], [
            'to.regex' => 'Número em formato E.164 (ex: +5511987654321 ou 5511987654321).',
        ]);

        $instance = $template->instance;
        if (!$instance || !$instance->isCloudApi()) {
            return response()->json(['ok' => false, 'error' => 'Instância vinculada ao template não é Cloud API.'], 422);
        }

        try {
            $sender = WhatsAppSenderFactory::forInstance($instance);
            $result = $sender->sendTemplate(
                to:           $data['to'],
                templateName: $template->name,
                languageCode: $template->language,
                variables:    array_values($data['variables'] ?? []),
            );
        } catch (Throwable $e) {
            Log::error('sendTest falhou', [
                'template_id' => $template->id,
                'error'       => $e->getMessage(),
            ]);
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }

        return response()->json($result);
    }
}
