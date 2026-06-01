<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class TenantBrandingController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant;

        if (!$tenant) {
            return redirect()->route('dashboard')->with('error', 'Tenant não encontrado.');
        }

        return view('settings.branding', compact('tenant'));
    }

    public function update(Request $request)
    {
        $tenant = auth()->user()->tenant;

        if (!$tenant) {
            return back()->with('error', 'Tenant não encontrado.');
        }

        $request->validate([
            'brand_name'            => 'nullable|string|max:80',
            'brand_color'           => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'brand_logo'            => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'weekly_report_enabled' => 'boolean',
            'report_email'          => 'nullable|email|max:100',
            'pix_key'               => 'nullable|string|max:100',
            'pix_key_type'          => 'nullable|string|in:cpf_cnpj,email,phone,random',
            'whatsapp_support'      => 'nullable|string|max:20',
            'openpix_app_id'        => 'nullable|string|max:150',
        ]);

        $data = [
            'brand_name'            => $request->input('brand_name'),
            'brand_color'           => $request->input('brand_color', '#4F46E5'),
            'weekly_report_enabled' => $request->boolean('weekly_report_enabled'),
            'report_email'          => $request->input('report_email'),
            'pix_key'               => $request->input('pix_key'),
            'pix_key_type'          => $request->input('pix_key_type'),
            'whatsapp_support'      => $request->input('whatsapp_support'),
            'openpix_app_id'        => $request->input('openpix_app_id'),
        ];

        // Upload da logo
        if ($request->hasFile('brand_logo')) {
            // Remove logo anterior
            if ($tenant->brand_logo) {
                Storage::disk('public')->delete($tenant->brand_logo);
            }

            $path = $request->file('brand_logo')->store("logos/{$tenant->id}", 'public');
            $data['brand_logo'] = $path;
        }

        $tenant->update($data);
        Cache::forget("tenant.{$tenant->id}");

        return back()->with('success', 'Identidade visual atualizada com sucesso!');
    }

    public function removeLogo()
    {
        $tenant = auth()->user()->tenant;

        if (!$tenant) {
            return response()->json(['error' => 'Tenant não encontrado.'], 404);
        }

        if ($tenant->brand_logo) {
            Storage::disk('public')->delete($tenant->brand_logo);
            $tenant->update(['brand_logo' => null]);
        }

        return response()->json(['success' => true]);
    }
}
