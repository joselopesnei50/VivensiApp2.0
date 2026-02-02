<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function index()
    {
        // Permission check (Simple for now, can be middleware)
        if (auth()->user()->role !== 'super_admin') {
            return redirect('/dashboard')->with('error', 'Acesso não autorizado.');
        }

        $deepseek_key = SystemSetting::getValue('deepseek_api_key');
        $gemini_key = SystemSetting::getValue('gemini_api_key');
        $brevo_key = SystemSetting::getValue('brevo_api_key');
        $asaas_key = SystemSetting::getValue('asaas_api_key');
        $asaas_env = SystemSetting::getValue('asaas_environment', 'sandbox');
        $email_from = SystemSetting::getValue('email_from');
        $email_from_name = SystemSetting::getValue('email_from_name');
        $home_video_url = SystemSetting::getValue('home_video_url');

        return view('admin.settings.index', compact('deepseek_key', 'gemini_key', 'brevo_key', 'asaas_key', 'asaas_env', 'email_from', 'email_from_name', 'home_video_url'));

    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403);
        }

        SystemSetting::setValue('deepseek_api_key', $request->deepseek_api_key, 'api');
        SystemSetting::setValue('gemini_api_key', $request->gemini_api_key, 'api');
        SystemSetting::setValue('brevo_api_key', $request->brevo_api_key, 'api');
        SystemSetting::setValue('asaas_api_key', $request->asaas_api_key, 'api');
        SystemSetting::setValue('asaas_environment', $request->asaas_environment, 'api');
        SystemSetting::setValue('email_from', $request->email_from, 'email');
        SystemSetting::setValue('email_from_name', $request->email_from_name, 'email');
        SystemSetting::setValue('home_video_url', $request->home_video_url, 'marketing');


        return redirect()->back()->with('success', 'Configurações de API atualizadas com sucesso!');
    }
}
