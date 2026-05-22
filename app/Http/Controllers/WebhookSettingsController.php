<?php

namespace App\Http\Controllers;

use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookSettingsController extends Controller
{
    public function index()
    {
        $webhooks = Webhook::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $events = WebhookService::EVENTS;

        return view('settings.webhooks', compact('webhooks', 'events'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'url'      => 'required|url|max:500',
            'events'   => 'required|array|min:1',
            'events.*' => 'in:' . implode(',', WebhookService::EVENTS) . ',*',
        ]);

        Webhook::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name'      => $request->input('name'),
            'url'       => $request->input('url'),
            'secret'    => Str::random(32),
            'events'    => $request->input('events'),
            'active'    => true,
        ]);

        return back()->with('success', 'Webhook criado com sucesso.');
    }

    public function toggle(int $id)
    {
        $webhook = Webhook::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $webhook->update(['active' => !$webhook->active]);
        return back()->with('success', $webhook->active ? 'Webhook ativado.' : 'Webhook desativado.');
    }

    public function destroy(int $id)
    {
        Webhook::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id)->delete();
        return back()->with('success', 'Webhook removido.');
    }

    public function logs(int $id)
    {
        $webhook = Webhook::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $logs    = $webhook->logs()->orderBy('fired_at', 'desc')->limit(50)->get();
        return response()->json($logs);
    }

    public function retry(int $id, int $logId)
    {
        $webhook = Webhook::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $log     = $webhook->logs()->findOrFail($logId);

        \App\Jobs\DispatchWebhook::dispatch($webhook->id, $log->event, $log->payload);

        return back()->with('success', "Webhook '{$log->event}' reenviado.");
    }
}
