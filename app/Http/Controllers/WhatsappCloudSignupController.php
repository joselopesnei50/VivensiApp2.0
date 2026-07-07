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
}
