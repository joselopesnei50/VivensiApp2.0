<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesLead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'origin',
        'plan_id',
        'estimated_value',
        'responsible_id',
        'next_step',
        'next_step_date',
        'notes',
        'stage_id',
        'position',
        'meeting_booking_id',
        'converted_tenant_id',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'next_step_date'  => 'date',
    ];

    public function stage()
    {
        return $this->belongsTo(SalesStage::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function meetingBooking()
    {
        return $this->belongsTo(MeetingBooking::class);
    }

    public function convertedTenant()
    {
        return $this->belongsTo(Tenant::class, 'converted_tenant_id');
    }

    public function activities()
    {
        return $this->hasMany(SalesLeadActivity::class, 'lead_id')->orderByDesc('created_at');
    }

    public function originLabel(): string
    {
        return match($this->origin) {
            'demo_agendada' => 'Demo Agendada',
            'indicacao'     => 'Indicação',
            default         => 'Manual',
        };
    }
}
