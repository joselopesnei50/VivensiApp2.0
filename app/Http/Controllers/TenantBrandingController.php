<?php

namespace App\Http\Controllers;

use App\Support\PixKey;
use App\Support\PixPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class TenantBrandingController extends Controller
{
    private const TABS = ['identidade', 'pix', 'relatorio'];

    public function index(Request $request)
    {
        $tenant = auth()->user()->tenant;

        if (!$tenant) {
            return redirect()->route('dashboard')->with('error', 'Tenant não encontrado.');
        }

        $pixType = in_array($tenant->pix_key_type, PixKey::TYPES, true)
            ? $tenant->pix_key_type
            : PixKey::detect($tenant->pix_key);
        $pixKey  = $tenant->pix_key ? PixKey::forPayload($tenant->pix_key, $pixType) : null;

        $tab = session('tab', $request->query('tab'));
        $tab = in_array($tab, self::TABS, true) ? $tab : 'identidade';

        return view('settings.branding', [
            'tenant'        => $tenant,
            'pixType'       => $pixType,
            'pixKey'        => $pixKey,
            'pixDisplay'    => $pixKey ? PixKey::display($pixKey, $pixType) : null,
            'pixLegacy'     => $tenant->pix_key && ($pixKey !== $tenant->pix_key || !$tenant->pix_key_type),
            'ownerWarning'  => $this->ownerWarning($tenant, $pixType, $pixKey),
            'merchantName'  => PixPayload::merchantName($tenant),
            'merchantCity'  => PixPayload::merchantCity($tenant),
            'tab'           => $tab,
        ]);
    }

    public function update(Request $request)
    {
        $tenant = auth()->user()->tenant;

        if (!$tenant) {
            return back()->with('error', 'Tenant não encontrado.');
        }

        $tab = in_array($request->input('_tab'), self::TABS, true) ? $request->input('_tab') : 'identidade';

        $request->validate([
            'brand_name'            => 'nullable|string|max:80',
            'brand_color'           => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            // SVG removido: pode embutir <script> e vira XSS servido do nosso domínio.
            'brand_logo'            => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'weekly_report_enabled' => 'boolean',
            'report_email'          => 'nullable|email|max:100',
            'pix_key'               => 'nullable|string|max:100',
            'pix_key_type'          => 'nullable|required_with:pix_key|string|in:' . implode(',', PixKey::TYPES),
            'whatsapp_support'      => 'nullable|string|max:25',
        ], [
            'brand_logo.image'          => 'O logotipo precisa ser uma imagem PNG, JPG ou WEBP.',
            'brand_logo.mimes'          => 'O logotipo precisa ser PNG, JPG ou WEBP (SVG não é aceito por segurança).',
            'brand_logo.max'            => 'O logotipo pode ter no máximo 2 MB.',
            'brand_color.regex'         => 'Cor inválida.',
            'report_email.email'        => 'E-mail do relatório inválido.',
            'pix_key_type.required_with'=> 'Escolha o tipo da chave Pix.',
            'pix_key_type.in'           => 'Tipo de chave Pix inválido.',
        ]);

        $data = [];

        // Só altera o que veio no formulário (um envio parcial não apaga o resto).
        foreach (['brand_name', 'report_email'] as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->input($field) ?: null;
            }
        }
        if ($request->has('brand_color')) {
            $data['brand_color'] = $request->input('brand_color') ?: '#4F46E5';
        }
        if ($request->has('weekly_report_enabled')) {
            $data['weekly_report_enabled'] = $request->boolean('weekly_report_enabled');
        }

        if ($request->has('pix_key')) {
            $raw = trim((string) $request->input('pix_key'));
            if ($raw === '') {
                $data['pix_key'] = null;
                $data['pix_key_type'] = null;
            } else {
                $type = $request->input('pix_key_type');
                $key  = PixKey::normalize($type, $raw);
                if ($key === null) {
                    return back()->withInput()->with('tab', 'pix')
                        ->withErrors(['pix_key' => PixKey::errorMessage($type)]);
                }
                $data['pix_key'] = $key;
                $data['pix_key_type'] = $type;
            }
        }

        if ($request->has('whatsapp_support')) {
            $phone = $this->normalizeWhatsapp($request->input('whatsapp_support'));
            if ($phone === false) {
                return back()->withInput()->with('tab', 'pix')
                    ->withErrors(['whatsapp_support' => 'WhatsApp inválido. Informe DDD + número, ex.: (16) 98839-2853.']);
            }
            $data['whatsapp_support'] = $phone;
        }

        if ($request->hasFile('brand_logo')) {
            if ($tenant->brand_logo) {
                Storage::disk('public')->delete($tenant->brand_logo);
            }
            $data['brand_logo'] = $request->file('brand_logo')->store("logos/{$tenant->id}", 'public');
        }

        $tenant->update($data);
        Cache::forget("tenant.{$tenant->id}");

        $msg = $tab === 'pix' ? 'Dados de recebimento salvos.' : 'Configurações salvas com sucesso!';
        if (isset($data['pix_key']) && $data['pix_key'] !== $request->input('pix_key')) {
            $msg .= ' A chave Pix foi gravada no formato do Banco Central.';
        }

        return redirect()->route('settings.branding')->with('success', $msg)->with('tab', $tab);
    }

    /**
     * Confere a chave digitada (sem salvar) e devolve o Pix de teste de R$ 1,00,
     * para a organização pagar no app do banco e ver se cai na conta certa.
     */
    public function pixPreview(Request $request)
    {
        $tenant = auth()->user()->tenant;
        abort_unless($tenant, 404);

        $request->validate([
            'pix_key'      => 'required|string|max:100',
            'pix_key_type' => 'required|string|in:' . implode(',', PixKey::TYPES),
        ]);

        $type = $request->input('pix_key_type');
        $key  = PixKey::normalize($type, $request->input('pix_key'));

        if ($key === null) {
            return response()->json(['ok' => false, 'message' => PixKey::errorMessage($type)], 422);
        }

        return response()->json([
            'ok'       => true,
            'key'      => $key,
            'display'  => PixKey::display($key, $type),
            'name'     => PixPayload::merchantName($tenant),
            'city'     => PixPayload::merchantCity($tenant),
            'payload'  => PixPayload::build($key, PixPayload::merchantName($tenant), PixPayload::merchantCity($tenant), 1.00, 'TESTEVIVENSI'),
            'warning'  => $this->ownerWarning($tenant, $type, $key),
        ]);
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

    /**
     * Aviso quando a chave não é do CNPJ da organização: quem paga vê o nome do titular
     * da chave no app do banco, e um CPF de pessoa física gera desconfiança.
     */
    private function ownerWarning($tenant, ?string $type, ?string $key): ?string
    {
        if (!$key) {
            return null;
        }

        $doc = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', (string) $tenant->document));

        if ($type === 'cpf_cnpj' && strlen($key) === 11) {
            return 'Esta chave é um CPF (pessoa física). Para rifas e doações, use uma chave da conta da organização, no CNPJ: no app do banco, quem paga vê o nome do titular da chave.';
        }
        if ($type === 'cpf_cnpj' && strlen($key) === 14 && strlen($doc) === 14 && $doc !== $key) {
            return 'Este CNPJ é diferente do CNPJ cadastrado da organização (' . PixKey::display($doc, 'cpf_cnpj') . '). Confira se a chave é da conta certa.';
        }

        return null;
    }

    /** Só dígitos com 55 na frente; '' vira null; formato inválido devolve false. */
    private function normalizeWhatsapp(?string $raw): string|null|false
    {
        $digits = preg_replace('/\D/', '', (string) $raw);
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 10 || strlen($digits) === 11) {
            $digits = '55' . $digits;
        }

        return preg_match('/^55[1-9][1-9]\d{8,9}$/', $digits) ? $digits : false;
    }
}
