<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\WebChatConversation as Conversation;
use App\Models\WebChatMessage as Message;
use App\Services\WebChat\WebChatService;
use Illuminate\Http\Request;

/** Caixa de atendimento do chat do site (super admin). */
class WebChatController extends Controller
{
    public function __construct(private WebChatService $chat) {}

    public function index(Request $request)
    {
        $filter = $request->query('status', 'abertas');
        $q = trim((string) $request->query('q', ''));

        $query = Conversation::query()->with(['assignedUser:id,name', 'booking'])
            ->where('visitor_messages', '>', 0)
            ->orderByRaw("CASE status WHEN 'waiting_human' THEN 0 WHEN 'human' THEN 1 WHEN 'bot' THEN 2 ELSE 3 END")
            ->orderByDesc('last_message_at');

        match ($filter) {
            'abertas'   => $query->where('status', '!=', Conversation::STATUS_CLOSED),
            'equipe'    => $query->whereIn('status', [Conversation::STATUS_WAITING_HUMAN, Conversation::STATUS_HUMAN]),
            'agendadas' => $query->whereNotNull('meeting_booking_id'),
            'encerradas'=> $query->where('status', Conversation::STATUS_CLOSED),
            default     => null,
        };
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('visitor_name', 'like', "%{$q}%")
                ->orWhere('visitor_email', 'like', "%{$q}%")
                ->orWhere('visitor_phone', 'like', "%{$q}%"));
        }

        $conversations = $query->paginate(25)->withQueryString();
        $lastMessages = Message::whereIn('id', function ($sub) use ($conversations) {
            $sub->selectRaw('MAX(id)')->from('web_chat_messages')
                ->whereIn('web_chat_conversation_id', $conversations->pluck('id'))
                ->whereIn('sender', [Message::SENDER_VISITOR, Message::SENDER_BOT, Message::SENDER_AGENT])
                ->groupBy('web_chat_conversation_id');
        })->get()->keyBy('web_chat_conversation_id');

        $since = now()->subDays(30);
        $base = Conversation::where('visitor_messages', '>', 0)->where('created_at', '>=', $since);
        $stats = [
            'conversations' => (clone $base)->count(),
            'bookings'      => (clone $base)->whereNotNull('meeting_booking_id')->count(),
            'handoffs'      => (clone $base)->whereNotNull('human_requested_at')->count(),
            'waiting'       => Conversation::where('status', Conversation::STATUS_WAITING_HUMAN)->count(),
        ];

        return view('admin.web_chat.index', [
            'conversations' => $conversations,
            'lastMessages'  => $lastMessages,
            'filter'        => $filter,
            'q'             => $q,
            'stats'         => $stats,
            'enabled'       => SystemSetting::getValue('web_chat_enabled', '0') === '1',
            'brunoReady'    => WebChatService::brunoTenantId() > 0,
        ]);
    }

    public function show(Conversation $conversation)
    {
        $conversation->forceFill(['unread_for_team' => 0])->save();
        $conversation->load(['messages.user:id,name', 'assignedUser:id,name', 'booking']);

        return view('admin.web_chat.show', ['c' => $conversation]);
    }

    /** Atualização ao vivo da conversa aberta no painel. */
    public function poll(Request $request, Conversation $conversation)
    {
        $after = (int) $request->query('depois', 0);
        $messages = $conversation->messages()->with('user:id,name')->where('id', '>', $after)->get();
        if ($messages->contains('sender', Message::SENDER_VISITOR)) {
            $conversation->forceFill(['unread_for_team' => 0])->save();
        }

        return response()->json([
            'status'       => $conversation->status,
            'status_label' => $conversation->statusLabel(),
            'messages'     => $messages->map(fn (Message $m) => [
                'id'     => $m->id,
                'sender' => $m->sender,
                'html'   => view('admin.web_chat._message', ['m' => $m])->render(),
            ])->values(),
        ]);
    }

    public function reply(Request $request, Conversation $conversation)
    {
        $data = $request->validate(['body' => 'required|string|max:2000']);
        $this->chat->agentReply($conversation, $request->user(), $data['body']);

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : redirect()->route('admin.web_chat.show', $conversation);
    }

    public function assume(Request $request, Conversation $conversation)
    {
        $this->chat->assume($conversation, $request->user());
        return redirect()->route('admin.web_chat.show', $conversation)->with('success', 'Você assumiu a conversa. O Bruno parou de responder até você devolver.');
    }

    public function release(Conversation $conversation)
    {
        $this->chat->releaseToBot($conversation);
        return redirect()->route('admin.web_chat.show', $conversation)->with('success', 'Conversa devolvida ao Bruno.');
    }

    public function close(Conversation $conversation)
    {
        $this->chat->close($conversation);
        return redirect()->route('admin.web_chat.index')->with('success', 'Conversa encerrada.');
    }

    public function settings(Request $request)
    {
        $on = $request->boolean('enabled');
        if ($on && WebChatService::brunoTenantId() <= 0) {
            return back()->with('error', 'Configure primeiro o tenant do Bruno (bot vendedor) nas configurações do sistema.');
        }
        SystemSetting::setValue('web_chat_enabled', $on ? '1' : '0');

        return back()->with('success', $on ? 'Chat do site ligado. O Bruno já está atendendo no site.' : 'Chat do site desligado.');
    }
}
