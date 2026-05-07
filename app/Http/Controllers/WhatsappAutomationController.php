<?php

namespace App\Http\Controllers;

use App\Models\WhatsappAutomation;
use App\Models\WhatsappAutomationLog;
use Illuminate\Http\Request;

class WhatsappAutomationController extends Controller
{
    public function index()
    {
        $automations = WhatsappAutomation::withCount('logs')->latest()->get();
        return view('whatsapp.automations.index', compact('automations'));
    }

    public function create()
    {
        return view('whatsapp.automations.form', ['automation' => null]);
    }

    public function store(Request $request)
    {
        WhatsappAutomation::create($this->validated($request));
        return redirect()->route('whatsapp.automations.index')->with('success', 'Automação criada com sucesso!');
    }

    public function edit(WhatsappAutomation $automation)
    {
        return view('whatsapp.automations.form', compact('automation'));
    }

    public function update(Request $request, WhatsappAutomation $automation)
    {
        $automation->update($this->validated($request));
        return redirect()->route('whatsapp.automations.index')->with('success', 'Automação atualizada!');
    }

    public function destroy(WhatsappAutomation $automation)
    {
        $automation->delete();
        return back()->with('success', 'Automação removida.');
    }

    public function toggle(WhatsappAutomation $automation)
    {
        $automation->update(['is_active' => !$automation->is_active]);
        return back()->with('success', $automation->is_active ? 'Automação ativada.' : 'Automação pausada.');
    }

    public function logs(WhatsappAutomation $automation)
    {
        $logs = $automation->logs()->latest('sent_at')->paginate(50);
        return view('whatsapp.automations.logs', compact('automation', 'logs'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'              => 'required|string|max:120',
            'trigger'           => 'required|in:no_contact_days,open_conversation_days,donor_inactive_days,sponsorship_stale_days',
            'trigger_days'      => 'required|integer|min:1|max:365',
            'message_template'  => 'required|string|max:2000',
            'audience'          => 'required|in:all,donors,sponsors,contacts',
            'send_window_start' => 'required|date_format:H:i',
            'send_window_end'   => 'required|date_format:H:i|after:send_window_start',
            'is_active'         => 'required|boolean',
        ]);
    }
}
