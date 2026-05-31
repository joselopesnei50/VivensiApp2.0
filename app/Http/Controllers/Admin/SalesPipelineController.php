<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MeetingBooking;
use App\Models\SalesLead;
use App\Models\SalesLeadActivity;
use App\Models\SalesStage;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

class SalesPipelineController extends Controller
{
    // ── Board ─────────────────────────────────────────────────────────────────

    public function board(Request $request)
    {

        $stages = SalesStage::ordered()
            ->with(['leads' => function ($q) use ($request) {
                if ($request->filled('responsible')) {
                    $q->where('responsible_id', $request->responsible);
                }
                if ($request->filled('origin')) {
                    $q->where('origin', $request->origin);
                }
                $q->with(['plan', 'responsible'])->orderBy('position');
            }])
            ->get();

        $plans   = SubscriptionPlan::where('is_active', true)->orderBy('name')->get();
        $admins  = User::where('role', 'super_admin')->orderBy('name')->get();
        $tenants = Tenant::orderBy('name')->get();

        $totalFunnel = SalesLead::whereHas('stage', fn ($q) => $q->where('is_won', false)->where('is_lost', false))
            ->sum('estimated_value');

        $linkedIds       = SalesLead::whereNotNull('meeting_booking_id')->pluck('meeting_booking_id');
        $pendingBookings = MeetingBooking::whereNotIn('id', $linkedIds)->count();

        return view('admin.sales.board', compact(
            'stages', 'plans', 'admins', 'tenants', 'totalFunnel', 'pendingBookings'
        ));
    }

    // ── Criar lead ────────────────────────────────────────────────────────────

    public function store(Request $request)
    {

        $data = $request->validate([
            'name'            => 'required|string|max:120',
            'company'         => 'nullable|string|max:120',
            'email'           => 'nullable|email|max:150',
            'phone'           => 'nullable|string|max:30',
            'origin'          => 'required|in:manual,demo_agendada,indicacao',
            'plan_id'         => 'nullable|exists:subscription_plans,id',
            'estimated_value' => 'nullable|numeric|min:0',
            'responsible_id'  => 'nullable|exists:users,id',
            'next_step'       => 'nullable|string|max:255',
            'next_step_date'  => 'nullable|date',
            'notes'           => 'nullable|string|max:2000',
            'stage_id'        => 'required|exists:sales_stages,id',
        ]);

        $data['position'] = SalesLead::where('stage_id', $data['stage_id'])->max('position') + 1;

        $lead = SalesLead::create($data);

        SalesLeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => auth()->id(),
            'type'    => 'created',
            'content' => 'Lead criado.',
        ]);

        return response()->json(['success' => true, 'lead_id' => $lead->id]);
    }

    // ── Detalhe (JSON para painel lateral) ────────────────────────────────────

    public function show(SalesLead $lead)
    {

        return response()->json([
            'lead'       => $lead->load('stage', 'plan', 'responsible', 'convertedTenant', 'meetingBooking'),
            'activities' => $lead->activities()->with('user')->get(),
        ]);
    }

    // ── Editar ────────────────────────────────────────────────────────────────

    public function update(Request $request, SalesLead $lead)
    {

        $data = $request->validate([
            'name'            => 'required|string|max:120',
            'company'         => 'nullable|string|max:120',
            'email'           => 'nullable|email|max:150',
            'phone'           => 'nullable|string|max:30',
            'origin'          => 'required|in:manual,demo_agendada,indicacao',
            'plan_id'         => 'nullable|exists:subscription_plans,id',
            'estimated_value' => 'nullable|numeric|min:0',
            'responsible_id'  => 'nullable|exists:users,id',
            'next_step'       => 'nullable|string|max:255',
            'next_step_date'  => 'nullable|date',
            'notes'           => 'nullable|string|max:2000',
        ]);

        $lead->update($data);

        return response()->json(['success' => true]);
    }

    // ── Mover card (drag-and-drop) ────────────────────────────────────────────

    public function move(Request $request, SalesLead $lead)
    {

        $data = $request->validate([
            'stage_id' => 'required|exists:sales_stages,id',
            'position' => 'required|integer|min:0',
        ]);

        $newStage = SalesStage::findOrFail($data['stage_id']);
        $oldStage = $lead->stage;

        // Se caiu em "Ganho", sinaliza para o JS abrir o modal de conversão
        if ($newStage->is_won && !$lead->stage->is_won) {
            // Ainda move — o modal de conversão é opcional (opção B como fallback)
            $lead->update(['stage_id' => $data['stage_id'], 'position' => $data['position']]);

            SalesLeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id(),
                'type'    => 'stage_changed',
                'content' => "De \"{$oldStage->name}\" → \"{$newStage->name}\"",
            ]);

            return response()->json(['success' => true, 'needs_conversion' => true]);
        }

        $lead->update(['stage_id' => $data['stage_id'], 'position' => $data['position']]);

        if ($oldStage->id !== $newStage->id) {
            SalesLeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id(),
                'type'    => 'stage_changed',
                'content' => "De \"{$oldStage->name}\" → \"{$newStage->name}\"",
            ]);
        }

        return response()->json(['success' => true, 'needs_conversion' => false]);
    }

    // ── Converter em cliente (modal Ganho) ────────────────────────────────────

    public function convert(Request $request, SalesLead $lead)
    {

        $data = $request->validate([
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $lead->update(['converted_tenant_id' => $data['tenant_id'] ?? null]);

        $content = $data['tenant_id']
            ? 'Convertido e vinculado ao tenant ID ' . $data['tenant_id'] . '.'
            : 'Marcado como convertido (tenant a vincular depois).';

        SalesLeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => auth()->id(),
            'type'    => 'converted',
            'content' => $content,
        ]);

        return response()->json(['success' => true]);
    }

    // ── Anotação na timeline ──────────────────────────────────────────────────

    public function addNote(Request $request, SalesLead $lead)
    {

        $data = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        SalesLeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => auth()->id(),
            'type'    => 'note_added',
            'content' => $data['content'],
        ]);

        return response()->json(['success' => true]);
    }

    // ── Soft delete ───────────────────────────────────────────────────────────

    public function destroy(SalesLead $lead)
    {

        $lead->delete();

        return response()->json(['success' => true]);
    }

    // ── Importar MeetingBookings (Bloco 5) ────────────────────────────────────

    public function importBookings()
    {

        $demoStage = SalesStage::where('name', 'Demo Agendada')->first()
            ?? SalesStage::ordered()->first();

        $imported  = 0;
        $skipped   = 0;

        $linkedIds = SalesLead::whereNotNull('meeting_booking_id')->pluck('meeting_booking_id');
        MeetingBooking::whereNotIn('id', $linkedIds)->each(function (MeetingBooking $booking) use ($demoStage, &$imported, &$skipped) {
            // Dedupe por e-mail
            if ($booking->email && SalesLead::where('email', $booking->email)->exists()) {
                $skipped++;
                return;
            }

            $position = SalesLead::where('stage_id', $demoStage->id)->max('position') + 1;

            $lead = SalesLead::create([
                'name'               => $booking->name,
                'email'              => $booking->email,
                'phone'              => $booking->phone,
                'notes'              => $booking->notes,
                'origin'             => 'demo_agendada',
                'stage_id'           => $demoStage->id,
                'position'           => $position,
                'meeting_booking_id' => $booking->id,
            ]);

            SalesLeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id(),
                'type'    => 'created',
                'content' => "Importado da agenda de reuniões (demo em {$booking->meeting_date->format('d/m/Y')}).",
            ]);

            $imported++;
        });

        return response()->json([
            'success'  => true,
            'imported' => $imported,
            'skipped'  => $skipped,
        ]);
    }
}
