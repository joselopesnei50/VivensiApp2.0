<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\SystemSetting;

class MeetingBooking extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'notes',
        'meeting_date', 'meeting_time',
        'status', 'confirmation_token',
        'meeting_link', 'admin_notes',
    ];

    protected $casts = [
        'meeting_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function ($booking) {
            $booking->confirmation_token = Str::random(40);
        });
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Retorna os slots disponíveis para uma data, respeitando as
     * configurações da agenda salvas em SystemSetting.
     */
    public static function availableSlotsFor(string $date): array
    {
        // Lê configurações do banco (com defaults sensatos)
        $activeDays    = array_map('intval', explode(',', SystemSetting::getValue('booking_days', '1,2,3,4,5')));
        $startTime     = SystemSetting::getValue('booking_start_time', '09:00');
        $endTime       = SystemSetting::getValue('booking_end_time', '17:00');
        $slotMinutes   = (int) SystemSetting::getValue('booking_slot_duration', '30');
        $minAdvanceHrs = (int) SystemSetting::getValue('booking_min_advance', '1');

        $day    = \Carbon\Carbon::parse($date);
        $dayNum = (int) $day->format('N') % 7; // ISO: 1=Mon…7=Sun → convert: Mon=1,Sun=0

        // Verifica se o dia está ativo
        if (!in_array($dayNum, $activeDays)) {
            return [];
        }

        // Gera todos os slots do dia
        [$sh, $sm] = explode(':', $startTime);
        [$eh, $em] = explode(':', $endTime);

        $cursor = $day->copy()->setTime((int)$sh, (int)$sm, 0);
        $limit  = $day->copy()->setTime((int)$eh, (int)$em, 0);
        $all    = [];

        while ($cursor < $limit) {
            $all[] = $cursor->format('H:i');
            $cursor->addMinutes($slotMinutes);
        }

        // Slots já ocupados
        $booked = static::where('meeting_date', $date)
            ->where('status', 'confirmed')
            ->pluck('meeting_time')
            ->map(fn($t) => substr($t, 0, 5))
            ->toArray();

        $now     = \Carbon\Carbon::now('America/Sao_Paulo');
        $isToday = $day->isToday();

        return array_values(array_filter($all, function ($slot) use ($booked, $isToday, $now, $date, $minAdvanceHrs) {
            if (in_array($slot, $booked)) return false;

            $slotTime = \Carbon\Carbon::parse($date . ' ' . $slot, 'America/Sao_Paulo');

            // Antecedência mínima (se configurada)
            if ($minAdvanceHrs > 0 && $slotTime->lessThanOrEqualTo($now->copy()->addHours($minAdvanceHrs))) {
                return false;
            }

            // Slots no passado (para hoje)
            if ($isToday && $slotTime->lessThanOrEqualTo($now)) {
                return false;
            }

            return true;
        }));
    }
}
