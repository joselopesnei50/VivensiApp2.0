<?php

namespace App\Http\Controllers;

use App\Models\WebChatConversation as Conversation;
use App\Models\WebChatMessage as Message;
use App\Services\WebChat\WebChatException;
use App\Services\WebChat\WebChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints públicos do chat do site. O visitante prova que é dono da
 * conversa mandando o segredo no cabeçalho X-Chat-Token.
 */
class WebChatController extends Controller
{
    public function __construct(private WebChatService $chat) {}

    public function start(Request $request): JsonResponse
    {
        if (! WebChatService::isEnabled()) {
            return response()->json(['error' => 'O chat está indisponível no momento.'], 503);
        }
        if (filled($request->input('website'))) { // honeypot
            return response()->json(['error' => 'Não foi possível iniciar o chat.'], 422);
        }

        $data = $request->validate([
            'page_url'   => 'nullable|string|max:1000',
            'page_title' => 'nullable|string|max:300',
            'referrer'   => 'nullable|string|max:1000',
        ]);

        try {
            [$conversation, $secret] = $this->chat->start($request, $data['page_url'] ?? null, $data['page_title'] ?? null, $data['referrer'] ?? null);
        } catch (WebChatException $e) {
            return response()->json(['error' => $e->getMessage()], $e->status());
        }

        return response()->json([
            'conversation' => ['id' => $conversation->public_id, 'token' => $secret],
        ] + $this->state($conversation, 0), 201);
    }

    public function messages(Request $request, string $publicId): JsonResponse
    {
        $conversation = $this->authorized($request, $publicId);
        return response()->json($this->state($conversation, (int) $request->query('depois', 0)));
    }

    public function send(Request $request, string $publicId): JsonResponse
    {
        $conversation = $this->authorized($request, $publicId);
        if (! WebChatService::isEnabled()) {
            return response()->json(['error' => 'O chat está indisponível no momento.'], 503);
        }
        $data = $request->validate(['body' => 'required|string|max:' . config('web_chat.max_message_length')]);

        try {
            $this->chat->visitorSays($conversation, $data['body']);
        } catch (WebChatException $e) {
            return response()->json(['error' => $e->getMessage()], $e->status());
        }

        return response()->json($this->state($conversation->refresh(), (int) $request->input('depois', 0)));
    }

    public function human(Request $request, string $publicId): JsonResponse
    {
        $conversation = $this->authorized($request, $publicId);
        $this->chat->requestHuman($conversation);
        return response()->json($this->state($conversation->refresh(), (int) $request->input('depois', 0)));
    }

    public function slots(Request $request, string $publicId): JsonResponse
    {
        $this->authorized($request, $publicId);
        return response()->json(['days' => $this->chat->availableDays()]);
    }

    public function book(Request $request, string $publicId): JsonResponse
    {
        $conversation = $this->authorized($request, $publicId);
        $data = $request->validate([
            'name'  => 'required|string|min:3|max:120',
            'email' => 'required|email:rfc|max:190',
            'phone' => ['required', 'string', 'max:30', function ($attr, $value, $fail) {
                $digits = strlen(preg_replace('/\D/', '', (string) $value));
                if ($digits < 10 || $digits > 13) {
                    $fail('Informe o telefone com DDD.');
                }
            }],
            'date'  => 'required|date_format:Y-m-d',
            'time'  => 'required|date_format:H:i',
        ]);
        $data['phone'] = preg_replace('/[^\d+() -]/', '', $data['phone']);

        if ($conversation->meeting_booking_id) {
            return response()->json(['error' => 'Você já tem uma reunião marcada. Para remarcar, use o link do e-mail de confirmação.'], 422);
        }

        try {
            $this->chat->book($conversation, $data);
        } catch (WebChatException $e) {
            return response()->json(['error' => $e->getMessage()], $e->status());
        }

        return response()->json($this->state($conversation->refresh(), (int) $request->input('depois', 0)));
    }

    public function emailLink(Request $request, string $publicId): JsonResponse
    {
        $conversation = $this->authorized($request, $publicId);
        $data = $request->validate([
            'email' => 'required|email:rfc|max:190',
            'name'  => 'nullable|string|max:120',
        ]);

        $this->chat->sendResumeLink($conversation, $data['email'], $data['name'] ?? null);

        return response()->json($this->state($conversation->refresh(), (int) $request->input('depois', 0)));
    }

    /** Link assinado do e-mail: gera um novo segredo e devolve o visitante ao chat. */
    public function resume(Request $request, string $conversation)
    {
        $c = Conversation::where('public_id', $conversation)->firstOrFail();
        $secret = $c->rotateSecret();

        return response()
            ->view('web_chat.resume', ['publicId' => $c->public_id, 'token' => $secret])
            ->header('Cache-Control', 'no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    // ─────────────────────────────────────────────────────────────────────

    private function authorized(Request $request, string $publicId): Conversation
    {
        $conversation = Conversation::where('public_id', $publicId)->first();
        abort_unless($conversation && $conversation->checkSecret($request->header('X-Chat-Token')), 404);
        return $conversation;
    }

    private function state(Conversation $conversation, int $afterId): array
    {
        $messages = $conversation->messages()->with('user:id,name')
            ->where('id', '>', $afterId)->limit(200)->get();

        $lastVisitor = (int) $conversation->messages()->where('sender', Message::SENDER_VISITOR)->max('id');
        $lastAnswer  = (int) $conversation->messages()->whereIn('sender', [Message::SENDER_BOT, Message::SENDER_AGENT])->max('id');

        return [
            'status'      => $conversation->status,
            'bot_typing'  => $conversation->botIsActive() && $lastVisitor > $lastAnswer,
            'has_booking' => (bool) $conversation->meeting_booking_id,
            'has_email'   => (bool) $conversation->visitor_email,
            'messages'    => $messages->map->toPublicArray()->values(),
        ];
    }
}
