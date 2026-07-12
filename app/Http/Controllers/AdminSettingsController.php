<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminSettingsController extends Controller
{
    public function index()
    {
        // Permission check (Simple for now, can be middleware)
        if (!auth()->user()->isSuperAdmin()) {
            return redirect('/dashboard')->with('error', 'Acesso não autorizado.');
        }

        // ────────────────────────────────────────────────────────────────────────
        // REGRA DE SEGURANCA CRITICA — NAO REMOVER
        // ────────────────────────────────────────────────────────────────────────
        // Nunca retornar o valor real de um secret pra view — nem em input password.
        // <input type="password" value="sk_..."> ESCONDE do olho mas EXPOE no HTML.
        // DevTools > Elements > busca "sk_" vaza a chave em <5 segundos.
        //
        // Padrao correto:
        //   1. Aqui: retorne apenas $xxx_configured (bool) via SystemSetting::getValue()
        //   2. Na view: use <input value=""> + placeholder mostra badge "Configurada"
        //   3. No store(): so sobrescreva se input vier NAO-vazio (loop abaixo, linha ~200)
        //
        // Regressao coberta por tests/Feature/Admin/AdminSettingsSecurityTest.php.
        $deepseek_configured = (bool) SystemSetting::getValue('deepseek_api_key');
        $gemini_configured = (bool) SystemSetting::getValue('gemini_api_key');
        $brevo_configured = (bool) SystemSetting::getValue('brevo_api_key');
        $unsplash_configured = (bool) SystemSetting::getValue('unsplash_access_key');
        $zapi_configured = (bool) SystemSetting::getValue('zapi_instance_id') && (bool) SystemSetting::getValue('zapi_token');
        $serper_configured = (bool) SystemSetting::getValue('serper_api_key');
        $google_maps_configured = (bool) SystemSetting::getValue('google_maps_api_key');
        $meta_app_secret_configured = (bool) SystemSetting::getValue('meta_app_secret');
        $pusher_configured = (bool) SystemSetting::getValue('pusher_app_id') && (bool) SystemSetting::getValue('pusher_app_key');
        $openpix_configured = (bool) SystemSetting::getValue('openpix_app_id');
        $abacatepay_configured = (bool) SystemSetting::getValue('abacatepay_api_key');
        $together_ai_configured = (bool) SystemSetting::getValue('together_ai_api_key');
        $dev_page_password_configured = (bool) SystemSetting::getValue('dev_page_password');

        // Analytics — IDs não são segredos, exibidos em texto puro
        $ga4_measurement_id = SystemSetting::getValue('ga4_measurement_id');
        $gtm_container_id   = SystemSetting::getValue('gtm_container_id');

        $deepseek_key = null;
        $gemini_key = null;
        $brevo_key = null;
        $pusher_secret = null; // Sensitive

        $email_from = SystemSetting::getValue('email_from');
        $email_from_name = SystemSetting::getValue('email_from_name');
        $home_video_url = SystemSetting::getValue('home_video_url');
        $support_whatsapp = SystemSetting::getValue('support_whatsapp', '16997618695');
        $zapi_instance = SystemSetting::getValue('zapi_instance_id');
        $zapi_token = null; 
        $zapi_client_token = null; 

        // Pusher/Soketi
        $pusher_app_id = SystemSetting::getValue('pusher_app_id');
        $pusher_app_key = SystemSetting::getValue('pusher_app_key');
        $pusher_host = SystemSetting::getValue('pusher_host', '127.0.0.1');
        $pusher_port = SystemSetting::getValue('pusher_port', '6001');
        $pusher_scheme = SystemSetting::getValue('pusher_scheme', 'http');

        $openpix_app_id = SystemSetting::getValue('openpix_app_id');

        // AbacatePay
        $abacatepay_env = SystemSetting::getValue('abacatepay_environment', 'sandbox');
        $abacatepay_webhook_secret = null; // never exposed

        // Meta Social (Facebook/Instagram) — IDs only, secrets never shown
        $meta_social_app_id_configured  = (bool) SystemSetting::getValue('meta_social_app_id');
        $meta_social_app_secret_configured = (bool) SystemSetting::getValue('meta_social_app_secret');
        $meta_social_app_id = SystemSetting::getValue('meta_social_app_id'); // App ID is not a secret

        // Bot Vendedor "Bruno" — id do tenant designado como "Vivensi Comercial".
        // Conversas inbound nesse tenant viram Bruno automaticamente.
        $bruno_sales_bot_tenant_id = (int) SystemSetting::getValue('bruno_sales_bot_tenant_id', 0);
        try {
            $bruno_tenants = \App\Models\Tenant::orderBy('name')->get(['id', 'name']);
        } catch (\Throwable $e) {
            $bruno_tenants = collect();
        }

        // Booking / agenda settings — safe fallbacks se a tabela ainda não existir
        $booking_days          = SystemSetting::getValue('booking_days', '1,2,3,4,5');
        $booking_months        = SystemSetting::getValue('booking_months', '1,2,3,4,5,6,7,8,9,10,11,12');
        $booking_start_time    = SystemSetting::getValue('booking_start_time', '09:00');
        $booking_end_time      = SystemSetting::getValue('booking_end_time', '17:00');
        $booking_slot_duration = SystemSetting::getValue('booking_slot_duration', '30');
        $booking_min_advance   = SystemSetting::getValue('booking_min_advance', '1');

        try {
            $booking_total    = \App\Models\MeetingBooking::where('status', 'confirmed')->count();
            $booking_upcoming = \App\Models\MeetingBooking::where('status', 'confirmed')
                ->where('meeting_date', '>=', today())
                ->orderBy('meeting_date')->orderBy('meeting_time')
                ->limit(5)->get();
        } catch (\Exception $e) {
            $booking_total    = 0;
            $booking_upcoming = collect();
        }

        return view('admin.settings.index', compact(
            'deepseek_key',
            'gemini_key',
            'brevo_key',
            'deepseek_configured',
            'gemini_configured',
            'brevo_configured',
            'unsplash_configured',
            'zapi_configured',
            'serper_configured',
            'google_maps_configured',
            'pusher_configured',
            'meta_app_secret_configured',
            'email_from',
            'email_from_name',
            'home_video_url',
            'support_whatsapp',
            'zapi_instance',
            'zapi_token',
            'zapi_client_token',
            'pusher_app_id',
            'pusher_app_key',
            'pusher_secret',
            'pusher_host',
            'pusher_port',
            'pusher_scheme',
            'openpix_app_id',
            'openpix_configured',
            'booking_days',
            'booking_months',
            'booking_start_time',
            'booking_end_time',
            'booking_slot_duration',
            'booking_min_advance',
            'booking_total',
            'booking_upcoming',
            'meta_social_app_id',
            'meta_social_app_id_configured',
            'meta_social_app_secret_configured',
            'abacatepay_configured',
            'abacatepay_env',
            'abacatepay_webhook_secret',
            'together_ai_configured',
            'dev_page_password_configured',
            'ga4_measurement_id',
            'gtm_container_id',
            'bruno_sales_bot_tenant_id',
            'bruno_tenants'
        ));

    }

    public function store(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            // Booking / agenda
            'booking_days'          => 'nullable|array',
            'booking_days.*'        => 'integer|between:0,6',
            'booking_months'        => 'nullable|array',
            'booking_months.*'      => 'integer|between:1,12',
            'booking_start_time'    => 'nullable|date_format:H:i',
            'booking_end_time'      => 'nullable|date_format:H:i',
            'booking_slot_duration' => 'nullable|integer|in:15,30,45,60',
            'booking_min_advance'   => 'nullable|integer|in:0,1,2,4,24,48',
            // existing
            'deepseek_api_key' => 'nullable|string|max:5000',
            'gemini_api_key' => 'nullable|string|max:5000',
            'unsplash_access_key' => 'nullable|string|max:5000',
            'brevo_api_key' => 'nullable|string|max:5000',
            'email_from' => 'nullable|email|max:255',
            'email_from_name' => 'nullable|string|max:255',
            'home_video_url' => 'nullable|url|max:2048',
            'support_whatsapp' => 'nullable|string|max:20',
            'zapi_instance_id' => 'nullable|string|max:255',
            'zapi_token' => 'nullable|string|max:5000',
            'zapi_client_token' => 'nullable|string|max:5000',
            'serper_api_key' => 'nullable|string|max:5000',
            'google_maps_api_key' => 'nullable|string|max:5000',
            // Pusher/Soketi
            'pusher_app_id' => 'nullable|string|max:255',
            'pusher_app_key' => 'nullable|string|max:255',
            'pusher_app_secret' => 'nullable|string|max:255',
            'pusher_host' => 'nullable|string|max:255',
            'pusher_port' => 'nullable|string|max:10',
            'pusher_scheme' => 'nullable|in:http,https',
            'openpix_app_id'         => 'nullable|string|max:5000',
            'meta_app_secret'        => 'nullable|string|max:5000',
            'meta_social_app_id'     => 'nullable|string|max:255',
            'meta_social_app_secret' => 'nullable|string|max:5000',
            // AbacatePay
            'abacatepay_api_key'        => 'nullable|string|max:5000',
            'abacatepay_webhook_secret' => 'nullable|string|max:5000',
            'abacatepay_environment'    => 'nullable|in:sandbox,production',
            'together_ai_api_key'       => 'nullable|string|max:5000',
            'dev_page_password'         => 'nullable|string|min:8|max:255',
            'ga4_measurement_id'        => 'nullable|string|max:50|regex:/^G-[A-Z0-9]+$/',
            'gtm_container_id'          => 'nullable|string|max:50|regex:/^GTM-[A-Z0-9]+$/',
            'bruno_sales_bot_tenant_id' => 'nullable|integer|min:0',
        ]);

        // Only overwrite secret keys if user provided a non-empty value.
        foreach ([
            'deepseek_api_key' => 'api',
            'gemini_api_key' => 'api',
            'unsplash_access_key' => 'api',
            'brevo_api_key' => 'api',
            'zapi_token' => 'whatsapp',
            'zapi_client_token' => 'whatsapp',
            'serper_api_key'     => 'api',
            'google_maps_api_key'=> 'api',
            'pusher_app_secret'  => 'broadcasting',
            'openpix_app_id'         => 'api',
            'meta_app_secret'        => 'whatsapp',
            'meta_social_app_secret' => 'social',
            'abacatepay_api_key'        => 'api',
            'abacatepay_webhook_secret' => 'api',
            'together_ai_api_key'       => 'api',
        ] as $key => $group) {
            $val = trim((string) ($validated[$key] ?? ''));
            if ($val !== '') {
                SystemSetting::setValue($key, $val, $group);
            }
        }

        if (!empty($validated['email_from'])) {
            SystemSetting::setValue('email_from', $validated['email_from'], 'email');
        }
        if (!empty($validated['email_from_name'])) {
            SystemSetting::setValue('email_from_name', $validated['email_from_name'], 'email');
        }
        if (!empty($validated['home_video_url'])) {
            SystemSetting::setValue('home_video_url', $validated['home_video_url'], 'marketing');
        }
        if (!empty($validated['support_whatsapp'])) {
            // Remove non-digits
            $cleanPhone = preg_replace('/\D/', '', $validated['support_whatsapp']);
            SystemSetting::setValue('support_whatsapp', $cleanPhone, 'marketing');
        }
        if (!empty($validated['zapi_instance_id'])) {
            SystemSetting::setValue('zapi_instance_id', $validated['zapi_instance_id'], 'whatsapp');
        }

        // Pusher/Soketi generic fields
        foreach (['pusher_app_id', 'pusher_app_key', 'pusher_host', 'pusher_port', 'pusher_scheme'] as $field) {
            if (!empty($validated[$field])) {
                SystemSetting::setValue($field, $validated[$field], 'broadcasting');
            }
        }


        // Meta Social (Facebook/Instagram)
        if (!empty($validated['meta_social_app_id'])) {
            SystemSetting::setValue('meta_social_app_id', $validated['meta_social_app_id'], 'social');
        }

        // AbacatePay
        if (!empty($validated['abacatepay_environment'])) {
            SystemSetting::setValue('abacatepay_environment', $validated['abacatepay_environment'], 'api');
        }

        // Booking / agenda settings
        $days = array_map('intval', $validated['booking_days'] ?? []);
        SystemSetting::setValue('booking_days', implode(',', $days), 'booking');

        $months = array_map('intval', $validated['booking_months'] ?? []);
        SystemSetting::setValue('booking_months', implode(',', $months ?: [1,2,3,4,5,6,7,8,9,10,11,12]), 'booking');

        if (!empty($validated['booking_start_time'])) {
            SystemSetting::setValue('booking_start_time', $validated['booking_start_time'], 'booking');
        }
        if (!empty($validated['booking_end_time'])) {
            SystemSetting::setValue('booking_end_time', $validated['booking_end_time'], 'booking');
        }
        if (isset($validated['booking_slot_duration'])) {
            SystemSetting::setValue('booking_slot_duration', (string)$validated['booking_slot_duration'], 'booking');
        }
        if (isset($validated['booking_min_advance'])) {
            SystemSetting::setValue('booking_min_advance', (string)$validated['booking_min_advance'], 'booking');
        }

        // Estatísticas da página pública
        $statFields = [
            'stat_orgs_count', 'stat_orgs_label',
            'stat_projects_count', 'stat_projects_label',
            'stat_users_count', 'stat_users_label',
            'stat_rating_score', 'stat_rating_label',
            'stat_hero_badge', 'stat_impact_label',
        ];
        foreach ($statFields as $field) {
            if ($request->has($field)) {
                $val = trim((string) $request->input($field));
                if ($val !== '') {
                    SystemSetting::setValue($field, $val, 'site_stats');
                } else {
                    // Se deixou em branco, remove para usar o fallback do banco
                    \App\Models\SystemSetting::where('key', $field)->delete();
                    Cache::forget("system_setting.{$field}");
                }
            }
        }

        // Dev page password — stored as bcrypt hash, never as plain text
        $rawDevPw = trim((string) ($validated['dev_page_password'] ?? ''));
        if ($rawDevPw !== '') {
            SystemSetting::setValue('dev_page_password', \Illuminate\Support\Facades\Hash::make($rawDevPw), 'security');
        }

        // Analytics — IDs são públicos, podem ser limpos (campo vazio = remove tag)
        $ga4 = trim((string) ($validated['ga4_measurement_id'] ?? ''));
        if ($ga4 !== '') {
            SystemSetting::setValue('ga4_measurement_id', $ga4, 'analytics');
        } else {
            \App\Models\SystemSetting::where('key', 'ga4_measurement_id')->delete();
        }

        $gtm = trim((string) ($validated['gtm_container_id'] ?? ''));
        if ($gtm !== '') {
            SystemSetting::setValue('gtm_container_id', $gtm, 'analytics');
        } else {
            \App\Models\SystemSetting::where('key', 'gtm_container_id')->delete();
        }

        // Bruno — tenant id designado (0 = desligado, integer > 0 = tenant ativo)
        $brunoTid = (int) ($validated['bruno_sales_bot_tenant_id'] ?? 0);
        SystemSetting::setValue('bruno_sales_bot_tenant_id', $brunoTid, 'bruno');

        return redirect()->back()->with('success', 'Configurações de API atualizadas com sucesso!');
    }
}
