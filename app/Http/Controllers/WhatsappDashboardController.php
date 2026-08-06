<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WhatsappChatAssignment;
use App\Services\Messaging\ChatTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Dashboard de produtividade do WhatsApp (2026-08-05).
 *
 * Le a tabela whatsapp_chat_assignments (P1) e agrega:
 *  - KPIs do periodo (atendimentos, tempo medio, chats abertos, auto vs manual)
 *  - Ranking de agentes (atendimentos, tempo medio, transferencias)
 *  - Serie diaria pro grafico
 *
 * So gestor/admin ve. Escopo per-tenant via Gate access-manager +
 * where('tenant_id') explicito em toda query.
 */
class WhatsappDashboardController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('access-manager');

        $tenantId = auth()->user()->tenant_id;
        $period   = $request->query('period', '7d');
        $since    = $this->periodStart($period);

        $kpis    = $this->kpis($tenantId, $since);
        $ranking = $this->ranking($tenantId, $since);
        $series  = $this->dailySeries($tenantId, $since);

        return view('whatsapp.dashboard', compact('kpis', 'ranking', 'series', 'period'));
    }

    private function periodStart(string $period): \Carbon\Carbon
    {
        return match ($period) {
            'today' => now()->startOfDay(),
            '30d'   => now()->subDays(30)->startOfDay(),
            default => now()->subDays(7)->startOfDay(), // 7d
        };
    }

    private function kpis(int $tenantId, \Carbon\Carbon $since): array
    {
        $inPeriod = WhatsappChatAssignment::where('tenant_id', $tenantId)
            ->where('started_at', '>=', $since);

        $totalOpen = (clone $inPeriod)->count();

        $completed = (clone $inPeriod)->whereNotNull('ended_at');
        $completedCount = (clone $completed)->count();
        $avgSeconds     = (int) (clone $completed)->avg('duration_seconds');

        $openNow = WhatsappChatAssignment::where('tenant_id', $tenantId)
            ->whereNull('ended_at')->count();

        $autoAssigned = (clone $inPeriod)->whereNull('assigned_by_user_id')->count();
        $manualCount  = $totalOpen - $autoAssigned;
        $autoPct      = $totalOpen > 0 ? round(($autoAssigned / $totalOpen) * 100) : 0;

        return [
            'total'          => $totalOpen,
            'completed'      => $completedCount,
            'avg_duration'   => $this->humanDuration($avgSeconds),
            'open_now'       => $openNow,
            'auto_pct'       => $autoPct,
            'manual_pct'     => 100 - $autoPct,
            'auto_count'     => $autoAssigned,
            'manual_count'   => $manualCount,
        ];
    }

    private function ranking(int $tenantId, \Carbon\Carbon $since): array
    {
        // Recebidos (to_user_id) — atendimentos e tempo medio.
        $received = WhatsappChatAssignment::query()
            ->selectRaw('to_user_id, COUNT(*) as total, AVG(duration_seconds) as avg_sec, SUM(CASE WHEN action = ? THEN 1 ELSE 0 END) as transfers_in', ['transfer'])
            ->where('tenant_id', $tenantId)
            ->where('started_at', '>=', $since)
            ->whereNotNull('to_user_id')
            ->groupBy('to_user_id')
            ->get()
            ->keyBy('to_user_id');

        // Transferencias/releases feitas (from_user_id).
        $done = WhatsappChatAssignment::query()
            ->selectRaw('from_user_id, COUNT(*) as transfers_out')
            ->where('tenant_id', $tenantId)
            ->where('started_at', '>=', $since)
            ->whereIn('action', ['transfer', 'release'])
            ->whereNotNull('from_user_id')
            ->groupBy('from_user_id')
            ->pluck('transfers_out', 'from_user_id');

        // Todos users com role agente no tenant — inclui zerados no ranking.
        $agents = User::where('tenant_id', $tenantId)
            ->whereIn('role', ChatTransferService::AGENT_ROLES)
            ->get(['id', 'name', 'agent_availability']);

        $rows = $agents->map(function (User $u) use ($received, $done) {
            $r = $received->get($u->id);
            return [
                'user_id'        => $u->id,
                'name'           => $u->name,
                'availability'   => $u->agent_availability ?? 'available',
                'total'          => (int) ($r->total ?? 0),
                'avg_duration'   => $this->humanDuration((int) ($r->avg_sec ?? 0)),
                'transfers_in'   => (int) ($r->transfers_in ?? 0),
                'transfers_out'  => (int) ($done->get($u->id, 0)),
            ];
        })
        ->sortByDesc('total')
        ->values()
        ->all();

        return $rows;
    }

    private function dailySeries(int $tenantId, \Carbon\Carbon $since): array
    {
        $rows = WhatsappChatAssignment::query()
            ->selectRaw('DATE(started_at) as day, COUNT(*) as total')
            ->where('tenant_id', $tenantId)
            ->where('started_at', '>=', $since)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        // Preenche dias sem registro pra o grafico nao ter buraco.
        $out = [];
        for ($d = $since->copy(); $d->lte(now()); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $out[] = [
                'day'   => $key,
                'label' => $d->format('d/m'),
                'total' => (int) ($rows->get($key)->total ?? 0),
            ];
        }
        return $out;
    }

    private function humanDuration(int $seconds): string
    {
        if ($seconds <= 0) return '—';
        if ($seconds < 60) return "{$seconds}s";
        $min = (int) floor($seconds / 60);
        if ($min < 60) return "{$min}min";
        $h = (int) floor($min / 60);
        $rem = $min % 60;
        return $rem > 0 ? "{$h}h {$rem}min" : "{$h}h";
    }
}
