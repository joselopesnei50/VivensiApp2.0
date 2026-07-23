<?php

namespace App\Http\Controllers\Ngo;

use App\Http\Controllers\Controller;
use App\Models\RadarFinding;
use App\Models\RadarMatch;
use App\Models\RadarNotification;
use App\Models\Tenant;
use App\Services\Radar\MatchingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RadarController extends Controller
{
    public function __construct(private MatchingService $matching) {}

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $tenant   = Tenant::findOrFail($tenantId);

        $matches = RadarMatch::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with('finding')
            ->whereHas('finding', fn($q) => $q->where('status', 'aprovado'))
            ->orderByDesc('score')
            ->paginate(20)
            ->withQueryString();

        // Mark viewed findings as 'painel' notification (idempotent)
        $findingIds = $matches->pluck('radar_finding_id');
        foreach ($findingIds as $fid) {
            RadarNotification::withoutGlobalScopes()->insertOrIgnore([[
                'tenant_id'        => $tenantId,
                'radar_finding_id' => $fid,
                'channel'          => 'painel',
                'sent_at'          => now(),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]]);
        }

        // Load feedback for this tenant
        $feedbacks = RadarNotification::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('channel', 'painel')
            ->whereIn('radar_finding_id', $findingIds)
            ->pluck('feedback', 'radar_finding_id');

        return view('ngo.radar.index', compact('matches', 'tenant', 'feedbacks'));
    }

    public function configurar(Request $request): RedirectResponse
    {
        $this->autorizarAdmin();

        $request->validate([
            'radar_ibge_code'      => ['nullable', 'string', 'size:7', 'regex:/^\d{7}$/'],
            'radar_areas'          => ['nullable', 'string'],
            'radar_digest_channel' => ['nullable', 'in:email,whatsapp,desligado'],
            'radar_min_score'      => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $areas = [];
        if ($request->filled('radar_areas')) {
            $areas = array_filter(
                array_map('trim', explode("\n", $request->input('radar_areas'))),
                fn($a) => $a !== ''
            );
        }

        $channel = $request->input('radar_digest_channel', 'desligado');

        Tenant::withoutGlobalScopes()
            ->where('id', auth()->user()->tenant_id)
            ->update([
                'radar_ibge_code'      => $request->input('radar_ibge_code') ?: null,
                'radar_areas'          => array_values($areas) ?: null,
                'radar_digest_channel' => $channel === 'desligado' ? null : $channel,
                'radar_min_score'      => (int) $request->input('radar_min_score', 30),
            ]);

        return back()->with('success', 'Configurações do Radar salvas.');
    }

    public function feedback(Request $request, int $finding): RedirectResponse
    {
        $request->validate([
            'feedback' => ['required', 'in:util,nao_util'],
        ]);

        $tenantId = auth()->user()->tenant_id;

        RadarNotification::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('radar_finding_id', $finding)
            ->where('channel', 'painel')
            ->update([
                'feedback'    => $request->input('feedback'),
                'feedback_at' => now(),
            ]);

        return back()->with('success', 'Obrigado pelo feedback!');
    }

    public function regenerar(): RedirectResponse
    {
        $this->autorizarAdmin();

        $tenantId = auth()->user()->tenant_id;
        $tenant   = Tenant::findOrFail($tenantId);

        $created = $this->matching->generateForTenant($tenant);

        return back()->with('success', "{$created} novos achados encontrados para o seu perfil.");
    }
}
