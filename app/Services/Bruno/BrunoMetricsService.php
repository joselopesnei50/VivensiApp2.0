<?php

namespace App\Services\Bruno;

use App\Models\KanbanCard;
use App\Models\Lead;
use App\Models\MeetingBooking;
use App\Models\SystemSetting;
use App\Models\WhatsappChat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Métricas do Bot Vendedor "Bruno" pro dashboard super_admin.
 * Cacheado por janela (default 5min) pra não martelar o DB.
 */
class BrunoMetricsService
{
    public const CACHE_TTL = 300; // 5 min

    public function summary(int $days = 7): array
    {
        $tenantId = (int) SystemSetting::getValue('bruno_sales_bot_tenant_id', 0);
        if ($tenantId <= 0) {
            return ['enabled' => false];
        }

        return Cache::remember("bruno.metrics.{$tenantId}.{$days}", self::CACHE_TTL, function () use ($tenantId, $days) {
            $since = Carbon::now()->subDays($days);

            $chats = WhatsappChat::where('tenant_id', $tenantId)
                ->where('created_at', '>=', $since)
                ->count();

            // Leads criados via Bruno na janela
            $leads = Lead::where('tenant_id', $tenantId)
                ->where('created_at', '>=', $since)
                ->get(['id', 'tags']);

            $leadsTotal = $leads->count();

            // Agrupa por qualificação (lê a tag "qual:<valor>" do array de tags)
            $byQualification = ['frio' => 0, 'morno' => 0, 'quente' => 0, 'sem' => 0];
            foreach ($leads as $lead) {
                $tags = is_array($lead->tags) ? $lead->tags : [];
                $qualTag = null;
                foreach ($tags as $t) {
                    if (is_string($t) && str_starts_with($t, 'qual:')) {
                        $qualTag = substr($t, 5);
                        break;
                    }
                }
                if ($qualTag && isset($byQualification[$qualTag])) {
                    $byQualification[$qualTag]++;
                } else {
                    $byQualification['sem']++;
                }
            }

            // Kanban cards criados pelo Bruno na janela
            $kanbanCards = KanbanCard::where('tenant_id', $tenantId)
                ->where('created_at', '>=', $since)
                ->get(['id', 'meta'])
                ->filter(function ($c) {
                    $m = is_array($c->meta) ? $c->meta : (is_string($c->meta) ? json_decode($c->meta, true) : []);
                    return is_array($m) && (($m['source'] ?? null) === 'bruno_sales_bot');
                })
                ->count();

            // Bookings via Bruno (filtrados pelo prefixo no notes)
            $bookingsTotal = MeetingBooking::where('created_at', '>=', $since)
                ->where('notes', 'like', '[via Bruno]%')
                ->count();

            $recentBookings = MeetingBooking::where('notes', 'like', '[via Bruno]%')
                ->where('status', 'confirmed')
                ->orderBy('meeting_date', 'desc')
                ->orderBy('meeting_time', 'desc')
                ->limit(10)
                ->get(['id', 'name', 'email', 'phone', 'meeting_date', 'meeting_time', 'status', 'created_at']);

            return [
                'enabled'           => true,
                'tenant_id'         => $tenantId,
                'days'              => $days,
                'since'             => $since->toDateTimeString(),
                'generated_at'      => now()->toDateTimeString(),
                'chats'             => $chats,
                'leads_total'       => $leadsTotal,
                'by_qualification'  => $byQualification,
                'kanban_cards'      => $kanbanCards,
                'bookings_total'    => $bookingsTotal,
                'recent_bookings'   => $recentBookings,
            ];
        });
    }
}
