<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    // Updated for PagSeguro Integration
    public function index()
    {
        // Permission check (Simple for now, can be middleware)
        if (auth()->user()->role !== 'super_admin') {
            return redirect('/dashboard')->with('error', 'Acesso não autorizado.');
        }

        // Never expose secret keys back to the browser (even in password inputs).
        $deepseek_configured = (bool) SystemSetting::getValue('deepseek_api_key');
        $gemini_configured = (bool) SystemSetting::getValue('gemini_api_key');
        $brevo_configured = (bool) SystemSetting::getValue('brevo_api_key');
        $pagseguro_configured = (bool) SystemSetting::getValue('pagseguro_token');
        $unsplash_configured = (bool) SystemSetting::getValue('unsplash_access_key');

        $deepseek_key = null;
        $gemini_key = null;
        $brevo_key = null;
        $pagseguro_key = null;

        $pagseguro_email = SystemSetting::getValue('pagseguro_email');
        $pagseguro_env = SystemSetting::getValue('pagseguro_environment', 'sandbox');
        $email_from = SystemSetting::getValue('email_from');
        $email_from_name = SystemSetting::getValue('email_from_name');
        $home_video_url = SystemSetting::getValue('home_video_url');

        return view('admin.settings.index', compact(
            'deepseek_key',
            'gemini_key',
            'brevo_key',
            'pagseguro_key',
            'deepseek_configured',
            'gemini_configured',
            'brevo_configured',
            'pagseguro_configured',
            'unsplash_configured',
            'pagseguro_email',
            'pagseguro_env',
            'email_from',
            'email_from_name',
            'home_video_url'
        ));

    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403);
        }

        $validated = $request->validate([
            'deepseek_api_key' => 'nullable|string|max:5000',
            'gemini_api_key' => 'nullable|string|max:5000',
            'unsplash_access_key' => 'nullable|string|max:5000',
            'brevo_api_key' => 'nullable|string|max:5000',
            'pagseguro_email' => 'nullable|email|max:255',
            'pagseguro_token' => 'nullable|string|max:5000',
            'pagseguro_environment' => 'required|in:sandbox,production',
            'email_from' => 'nullable|email|max:255',
            'email_from_name' => 'nullable|string|max:255',
            'home_video_url' => 'nullable|url|max:2048',
        ]);

        // Only overwrite secret keys if user provided a non-empty value.
        foreach ([
            'deepseek_api_key' => 'api',
            'gemini_api_key' => 'api',
            'unsplash_access_key' => 'api',
            'brevo_api_key' => 'api',
            'pagseguro_token' => 'api',
        ] as $key => $group) {
            $val = trim((string) ($validated[$key] ?? ''));
            if ($val !== '') {
                SystemSetting::setValue($key, $val, $group);
            }
        }

        SystemSetting::setValue('pagseguro_environment', $validated['pagseguro_environment'], 'api');
        
        if (!empty($validated['pagseguro_email'])) {
            SystemSetting::setValue('pagseguro_email', $validated['pagseguro_email'], 'api');
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


        return redirect()->back()->with('success', 'Configurações de API atualizadas com sucesso!');
    }
}
