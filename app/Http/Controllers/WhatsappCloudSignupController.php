<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\WhatsApp\CloudApiOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Fluxo de Embedded Signup do Meta WhatsApp Business Cloud API.
 *
 *   GET  /whatsapp/cloud/connect  → renderiza página com FB.login({config_id})
 *   POST /whatsapp/cloud/callback → recebe {code, waba_id, phone_number_id, pin} do JS,
 *                                    executa onboarding server-side, cria WhatsappInstance
 */
class WhatsappCloudSignupController extends Controller
{
    public function __construct(private CloudApiOnboardingService $onboarding) {}

    public function show()
    {
        Gate::authorize('access-whatsapp');

        return view('whatsapp.cloud-connect', [
            'appId'      => SystemSetting::getValue('meta_cloud_app_id', ''),
            'configId'   => SystemSetting::getValue('meta_cloud_config_id', ''),
            'configured' => $this->onboarding->isConfigured()
                            && !empty(SystemSetting::getValue('meta_cloud_config_id', '')),
        ]);
    }

    public function callback(Request $request)
    {
        Gate::authorize('access-whatsapp');

        $data = $request->validate([
            'code'            => ['required', 'string'],
            'waba_id'         => ['required', 'string'],
            'phone_number_id' => ['required', 'string'],
            'pin'             => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        try {
            $instance = $this->onboarding->completeSignup(
                tenantId:       (int) auth()->user()->tenant_id,
                code:           $data['code'],
                wabaId:         $data['waba_id'],
                phoneNumberId:  $data['phone_number_id'],
                registrationPin: $data['pin'],
            );
        } catch (\Throwable $e) {
            Log::error('WhatsApp Cloud signup falhou', [
                'tenant_id' => auth()->user()->tenant_id,
                'error'     => $e->getMessage(),
            ]);

            return response()->json([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok'          => true,
            'instance_id' => $instance->id,
            'redirect'    => route('whatsapp.instances'),
        ]);
    }

    /**
     * Onboarding assistido self-service — enquanto business_management não
     * está aprovado (Embedded Signup indisponível), o cliente cola 3
     * credenciais copiadas do próprio Business Manager e o Vivensi cadastra
     * a instance sem depender do FB.login.
     */
    public function showManual()
    {
        Gate::authorize('access-whatsapp');

        return view('whatsapp.cloud-manual-connect', [
            'configured' => $this->onboarding->isConfigured(),
        ]);
    }

    public function storeManual(Request $request)
    {
        Gate::authorize('access-whatsapp');

        $data = $request->validate([
            'waba_id'          => ['required', 'string', 'regex:/^\d{6,25}$/'],
            'phone_number_id'  => ['required', 'string', 'regex:/^\d{6,25}$/'],
            'access_token'     => ['required', 'string', 'min:100', 'max:500'],
            // PIN é opcional; usar só se o cliente ainda não registrou o número
            // no Business Manager. Se preenchido, precisa ter 6 dígitos.
            'pin'              => ['nullable', 'string', 'regex:/^\d{6}$/'],
        ], [
            'waba_id.regex'         => 'O WABA ID deve conter apenas números.',
            'phone_number_id.regex' => 'O Phone Number ID deve conter apenas números.',
            'access_token.min'      => 'O System User Access Token parece incompleto (deve ter pelo menos 100 caracteres).',
            'pin.regex'             => 'O PIN de verificação deve ter exatamente 6 dígitos numéricos.',
        ]);

        try {
            $instance = $this->onboarding->completeManualSignup(
                tenantId:        (int) auth()->user()->tenant_id,
                wabaId:          $data['waba_id'],
                phoneNumberId:   $data['phone_number_id'],
                accessToken:     $data['access_token'],
                registrationPin: $data['pin'] ?? null,
            );
        } catch (\Throwable $e) {
            Log::error('WhatsApp Cloud manual signup falhou', [
                'tenant_id' => auth()->user()->tenant_id,
                'error'     => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->except('access_token', 'pin'))
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('whatsapp.chat')
            ->with('success', "Número WhatsApp conectado com sucesso! (Instance #{$instance->id})");
    }
}
