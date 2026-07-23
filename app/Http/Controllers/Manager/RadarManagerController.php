<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\RadarMatch;
use App\Models\RadarNotification;

class RadarManagerController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        $matches = RadarMatch::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with('finding')
            ->whereHas('finding', fn($q) => $q->where('status', 'aprovado'))
            ->orderByDesc('score')
            ->paginate(20)
            ->withQueryString();

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

        $feedbacks = RadarNotification::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('channel', 'painel')
            ->whereIn('radar_finding_id', $findingIds)
            ->pluck('feedback', 'radar_finding_id');

        return view('manager.radar', compact('matches', 'feedbacks'));
    }
}
