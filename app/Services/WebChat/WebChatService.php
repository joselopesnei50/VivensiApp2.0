<?php

namespace App\Services\WebChat;

use App\Jobs\GenerateWebChatReplyJob;
use App\Mail\WebChatResumeMail;
use App\Models\MeetingBooking;
use App\Models\Notification;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WebChatConversation as Conversation;
use App\Models\WebChatMessage as Message;
use App\Services\BruceAiService;
use App\Services\Bruno\BrunoTools;
use App\Support\TrafficClassifier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Chat do site com o Bruno. Usa o mesmo cérebro do Bruno do WhatsApp
 * (BruceAiService, role sales_bot: mesmo prompt, lições, ferramentas de
 * agenda/planos/features) — só muda o canal. Nenhum código do WhatsApp
 * é alterado.
 */
class WebChatService
{
    public const CONTEXT_TYPE = 'web_chat';

    public function __construct(private BruceAiService $bruce) {}

    // ── Configuração ──────────────────────────────────────────────────────

    public static function brunoTenantId(): int
    {
        return (int) SystemSetting::getValue('bruno_sales_bot_tenant_id', 0);
    }

    public static function isEnabled(): bool
    {
        return SystemSetting::getValue('web_chat_enabled', '0') === '1' && self::brunoTenantId() > 0;
    }

    // ── Visitante ─────────────────────────────────────────────────────────

    /**
     * @return array{0: Conversation, 1: string}
     * @throws WebChatException
     */
    public function start(Request $request, ?string $pageUrl, ?string $pageTitle, ?string $pageReferrer): array
    {
        $ua = (string) $request->userAgent();
        if (TrafficClassifier::isBot($ua)) {
            throw new WebChatException('Não foi possível iniciar o chat.', 403);
        }

        $ip = (string) $request->ip();
        $today = Conversation::where('ip_address', $ip)->where('created_at', '>=', now()->subDay())->count();
        if ($today >= config('web_chat.max_conversations_per_ip_day')) {
            throw new WebChatException('Você já abriu muitas conversas hoje. Tente novamente amanhã ou agende direto pela agenda.', 429);
        }

        [$conversation, $secret] = Conversation::open([
            'page_url'   => $pageUrl ? mb_substr($pageUrl, 0, 500) : null,
            'page_title' => $pageTitle ? mb_substr(strip_tags($pageTitle), 0, 200) : null,
            'source'     => TrafficClassifier::source($pageReferrer),
            'device'     => TrafficClassifier::device($ua),
            'ip_address' => $ip,
            'user_agent' => mb_substr($ua, 0, 255),
            'last_message_at' => now(),
        ]);

        $conversation->addMessage(Message::SENDER_BOT, (string) config('web_chat.greeting'), ['kind' => 'greeting']);

        return [$conversation, $secret];
    }

    /** @throws WebChatException */
    public function visitorSays(Conversation $conversation, string $text): Message
    {
        $text = trim(preg_replace("/\r\n?/", "\n", $text));
        if ($text === '') {
            throw new WebChatException('Escreva uma mensagem.', 422);
        }
        $text = mb_substr($text, 0, (int) config('web_chat.max_message_length'));

        if ($conversation->visitor_messages >= config('web_chat.max_visitor_messages')) {
            throw new WebChatException('Esta conversa atingiu o limite de mensagens. Agende uma conversa com a equipe ou deixe seu e-mail.', 429);
        }

        if ($conversation->status === Conversation::STATUS_CLOSED) {
            $conversation->forceFill(['status' => Conversation::STATUS_BOT, 'closed_at' => null])->save();
        }

        $this->captureContact($conversation, $text);
        $message = $conversation->addMessage(Message::SENDER_VISITOR, $text);

        if ($conversation->botIsActive()) {
            GenerateWebChatReplyJob::dispatch($conversation->id)->afterCommit();
        }

        return $message;
    }

    public function requestHuman(Conversation $conversation, string $reason = 'visitante pediu'): void
    {
        if (in_array($conversation->status, [Conversation::STATUS_WAITING_HUMAN, Conversation::STATUS_HUMAN], true)) {
            return;
        }

        $conversation->forceFill([
            'status'             => Conversation::STATUS_WAITING_HUMAN,
            'human_requested_at' => now(),
        ])->save();

        $conversation->addMessage(Message::SENDER_SYSTEM,
            'Avisei a nossa equipe. Assim que alguém estiver disponível, responde aqui mesmo nesta conversa. '
            . ($conversation->visitor_email ? '' : 'Se preferir, deixe seu e-mail no menu ⋮ e a gente responde por lá.'),
            ['kind' => 'handoff']);

        $who = $conversation->displayName();
        foreach (User::where('role', 'super_admin')->pluck('id') as $adminId) {
            Notification::create([
                'user_id' => $adminId,
                'title'   => 'Chat do site: pedido de atendimento',
                'message' => "💬 {$who} quer falar com a equipe ({$reason}).",
                'type'    => 'web_chat',
                'link'    => '/admin/chat-site/' . $conversation->id,
                'read_at' => null,
            ]);
        }
    }

    /** Próximos dias com horários livres, no formato do widget. */
    public function availableDays(): array
    {
        $days = [];
        $cursor = Carbon::today();
        for ($i = 0; $i < (int) config('web_chat.booking_days_ahead'); $i++, $cursor = $cursor->copy()->addDay()) {
            $slots = MeetingBooking::availableSlotsFor($cursor->toDateString());
            if ($slots) {
                $days[] = [
                    'date'  => $cursor->toDateString(),
                    'label' => $cursor->isToday() ? 'Hoje' : ($cursor->isTomorrow() ? 'Amanhã' : ucfirst($cursor->locale('pt_BR')->isoFormat('ddd D/MM'))),
                    'slots' => array_values($slots),
                ];
            }
        }
        return $days;
    }

    /**
     * Agenda pela mesma ferramenta que o Bruno usa no WhatsApp (mesma agenda,
     * mesmos e-mails de confirmação e aviso aos admins).
     *
     * @throws WebChatException
     */
    public function book(Conversation $conversation, array $data): array
    {
        $result = BrunoTools::execute('agendar_reuniao', [
            'nome'        => $data['name'],
            'email'       => $data['email'],
            'telefone'    => $data['phone'],
            'data'        => $data['date'],
            'hora'        => $data['time'],
            'observacoes' => 'Chat do site' . ($conversation->page_title ? ' — ' . $conversation->page_title : ''),
        ]);

        if (empty($result['success'])) {
            throw new WebChatException($result['error'] ?? 'Não foi possível agendar. Tente outro horário.', 422);
        }

        $booking = MeetingBooking::where('confirmation_token', $result['token'])->first();
        $conversation->forceFill([
            'visitor_name'       => $data['name'],
            'visitor_email'      => $data['email'],
            'visitor_phone'      => $data['phone'],
            'meeting_booking_id' => $booking?->id,
        ])->save();

        $conversation->addMessage(Message::SENDER_SYSTEM,
            "✅ Reunião marcada para {$result['data_formatada']} às {$result['hora']}. "
            . "Enviamos a confirmação para {$data['email']}, com o link da reunião e a opção de remarcar.",
            ['kind' => 'booking']);

        return $result;
    }

    public function sendResumeLink(Conversation $conversation, string $email, ?string $name = null): void
    {
        $conversation->forceFill(array_filter([
            'visitor_email' => $email,
            'visitor_name'  => $name ?: $conversation->visitor_name,
        ]))->save();

        $url = URL::temporarySignedRoute('web_chat.resume', now()->addDays((int) config('web_chat.resume_link_days')), [
            'conversation' => $conversation->public_id,
        ]);

        Mail::to($email)->queue(new WebChatResumeMail($conversation, $url));

        $conversation->addMessage(Message::SENDER_SYSTEM,
            "📩 Enviamos para {$email} um link para você continuar esta conversa quando quiser.",
            ['kind' => 'resume_link']);
    }

    // ── Bruno ─────────────────────────────────────────────────────────────

    /** Gera a resposta do Bruno para as mensagens do visitante ainda sem resposta. */
    public function generateReply(int $conversationId): ?Message
    {
        $lock = Cache::lock("web_chat:reply:{$conversationId}", 120);
        if (! $lock->get()) {
            // Outro job já está respondendo; ele vai juntar as mensagens novas.
            GenerateWebChatReplyJob::dispatch($conversationId)->delay(now()->addSeconds(5));
            return null;
        }

        try {
            $conversation = Conversation::find($conversationId);
            if (! $conversation || ! $conversation->botIsActive()) {
                return null;
            }

            $lastAnswerId = (int) $conversation->messages()
                ->whereIn('sender', [Message::SENDER_BOT, Message::SENDER_AGENT])
                ->max('id');
            $pending = $conversation->messages()
                ->where('sender', Message::SENDER_VISITOR)
                ->where('id', '>', $lastAnswerId)
                ->pluck('body');

            if ($pending->isEmpty()) {
                return null;
            }

            if ($conversation->ai_replies >= config('web_chat.max_ai_replies') || ! $this->takeDailyAiSlot()) {
                $this->requestHuman($conversation, 'limite de respostas automáticas');
                return null;
            }

            $reply = null;
            try {
                $result = $this->bruce->chat(
                    userMessage: $pending->implode("\n"),
                    tenantId: self::brunoTenantId(),
                    role: 'sales_bot',
                    // userId negativo e único por conversa: isola histórico, segmento
                    // e qualificação deste visitante (o WhatsApp usa userId 0).
                    userId: -$conversation->id,
                    contextType: self::CONTEXT_TYPE,
                    contextId: $conversation->id,
                    leadContext: $this->leadContext($conversation),
                );
                if (empty($result['error'])) {
                    $reply = trim((string) ($result['reply'] ?? ''));
                } else {
                    Log::warning('WebChat: Bruno devolveu erro', ['conversation' => $conversation->id, 'err' => $result['error']]);
                }
            } catch (\Throwable $e) {
                Log::warning('WebChat: exceção no Bruno', ['conversation' => $conversation->id, 'err' => $e->getMessage()]);
            }

            if ($reply === null || $reply === '') {
                $msg = $conversation->addMessage(Message::SENDER_BOT,
                    'Tive um probleminha para responder agora. Você pode tentar de novo em instantes, agendar uma conversa pelo botão "Agendar" ou pedir para falar com a equipe.',
                    ['kind' => 'fallback']);
                return $msg;
            }

            $conversation->increment('ai_replies');
            $msg = $conversation->addMessage(Message::SENDER_BOT, $reply);

            if (self::replyIndicatesEscalation($reply)) {
                $this->requestHuman($conversation, 'Bruno passou o atendimento');
            }

            return $msg;
        } finally {
            $lock->release();
        }
    }

    /** Mesmas frases canônicas que o Bruno usa no WhatsApp ao escalar. */
    public static function replyIndicatesEscalation(string $reply): bool
    {
        $t = mb_strtolower($reply);
        $t = str_replace(['á','é','í','ó','ú','â','ê','ô','ã','õ','ç'], ['a','e','i','o','u','a','e','o','a','o','c'], $t);
        foreach (['atendimento humano', 'acionar o atendimento', 'passar pra humano', 'passar pro humano', 'conectar com um humano'] as $p) {
            if (str_contains($t, $p)) {
                return true;
            }
        }
        return false;
    }

    private function takeDailyAiSlot(): bool
    {
        $key = 'web_chat:ai_day:' . now()->toDateString();
        Cache::add($key, 0, now()->endOfDay());
        return Cache::increment($key) <= (int) config('web_chat.max_ai_replies_day');
    }

    /** Guarda nome, e-mail e telefone que o visitante escreveu (sem sobrescrever o que já temos). */
    private function captureContact(Conversation $conversation, string $text): void
    {
        $updates = [];
        if (! $conversation->visitor_email && ($email = VisitorContactExtractor::email($text))) {
            $updates['visitor_email'] = $email;
        }
        if (! $conversation->visitor_phone && ($phone = VisitorContactExtractor::phone($text))) {
            $updates['visitor_phone'] = $phone;
        }
        if (! $conversation->visitor_name) {
            $lastBot = $conversation->messages()->where('sender', Message::SENDER_BOT)->latest('id')->value('body');
            if ($name = VisitorContactExtractor::name($text, $lastBot)) {
                $updates['visitor_name'] = $name;
            }
        }
        if ($updates) {
            $conversation->forceFill($updates)->save();
        }
    }

    /** Orienta o Bruno a pedir nome e contato, uma vez cada, sem insistir. */
    private function identificationNote(Conversation $conversation): string
    {
        // Respostas do próprio Bruno (a saudação automática não conta como "já perguntei")
        $botTexts = $conversation->messages()->where('sender', Message::SENDER_BOT)->get(['body', 'meta'])
            ->reject(fn ($m) => in_array($m->meta['kind'] ?? null, ['greeting', 'fallback'], true))
            ->pluck('body');
        $askedName    = $botTexts->contains(fn ($t) => VisitorContactExtractor::botAskedName($t));
        $askedContact = $botTexts->contains(fn ($t) => VisitorContactExtractor::botAskedContact($t));
        $hasContact   = $conversation->visitor_email || $conversation->visitor_phone;

        if (! $conversation->visitor_name) {
            return $askedName
                ? ' IDENTIFICAÇÃO: você já perguntou o nome e ele não disse — não pergunte de novo, siga a conversa.'
                : ' IDENTIFICAÇÃO: você ainda não sabe o nome do lead. Responda primeiro o que ele perguntou e, no fim da resposta, pergunte de forma leve como pode chamá-lo (ex.: "Aliás, com quem eu falo?"). Só uma vez.';
        }

        $note = ' O lead se chama ' . $conversation->visitor_name . ' — use o primeiro nome com naturalidade, sem repetir em toda mensagem.';
        if ($hasContact) {
            return $note . ' Contato já registrado (' . collect([$conversation->visitor_phone, $conversation->visitor_email])->filter()->implode(' / ') . ') — não peça de novo.';
        }
        if ($askedContact) {
            return $note . ' Você já pediu o contato; se ele não passou, não insista.';
        }
        return $note . ' Ainda não temos contato: quando ele demonstrar interesse (planos, preço, proposta, agendar) ou antes de se despedir, peça de forma natural o WhatsApp ou e-mail para a equipe enviar material ou retornar. Só uma vez.';
    }

    private function leadContext(Conversation $conversation): array
    {
        $origin = 'Chat do site vivensi.app.br';
        if ($conversation->page_title) {
            $origin .= ' — página "' . $conversation->page_title . '"';
        }
        if ($conversation->source && $conversation->source !== 'direct' && $conversation->source !== 'internal') {
            $origin .= ' (chegou ao site por ' . TrafficClassifier::sourceLabel($conversation->source) . ')';
        }

        $note = 'CANAL: o lead está no chat do SITE, não no WhatsApp — nunca diga "aqui no WhatsApp". '
            . 'O chat tem um botão "Agendar" que mostra os horários livres; você também pode agendar pelas suas ferramentas. '
            . 'Se ele preferir falar com uma pessoa, use a frase canônica de atendimento humano.';

        if ($conversation->booking) {
            $b = $conversation->booking;
            $note .= ' O lead JÁ AGENDOU reunião para ' . Carbon::parse($b->meeting_date)->format('d/m') . ' às ' . $b->meeting_time . ' — não ofereça agendar de novo.';
        }
        $note .= $this->identificationNote($conversation);

        // Se a equipe conversou com o visitante, o Bruno precisa saber o que foi dito.
        $agentTalk = $conversation->messages()
            ->whereIn('sender', [Message::SENDER_AGENT])
            ->latest('id')->limit(4)->pluck('body')->reverse()->values();
        if ($agentTalk->isNotEmpty()) {
            $note .= ' A equipe humana já respondeu nesta conversa: "' . mb_substr($agentTalk->implode('" / "'), 0, 800) . '".';
        }

        return array_filter([
            'name'   => $conversation->visitor_name,
            'origin' => $origin,
            'note'   => $note,
        ]);
    }

    // ── Equipe ────────────────────────────────────────────────────────────

    public function agentReply(Conversation $conversation, User $agent, string $text): Message
    {
        $conversation->forceFill([
            'status'           => Conversation::STATUS_HUMAN,
            'assigned_user_id' => $conversation->assigned_user_id ?: $agent->id,
            'unread_for_team'  => 0,
            'closed_at'        => null,
        ])->save();

        return $conversation->addMessage(Message::SENDER_AGENT, trim($text), [], $agent->id);
    }

    public function assume(Conversation $conversation, User $agent): void
    {
        $conversation->forceFill([
            'status'           => Conversation::STATUS_HUMAN,
            'assigned_user_id' => $agent->id,
            'unread_for_team'  => 0,
        ])->save();
        $conversation->addMessage(Message::SENDER_SYSTEM, strtok((string) $agent->name, ' ') . ' da equipe Vivensi entrou na conversa.', ['kind' => 'agent_joined']);
    }

    public function releaseToBot(Conversation $conversation): void
    {
        $conversation->forceFill(['status' => Conversation::STATUS_BOT, 'assigned_user_id' => null])->save();
        $conversation->addMessage(Message::SENDER_SYSTEM, 'O Bruno voltou a acompanhar esta conversa.', ['kind' => 'bot_back']);
    }

    public function close(Conversation $conversation): void
    {
        $conversation->forceFill(['status' => Conversation::STATUS_CLOSED, 'closed_at' => now(), 'unread_for_team' => 0])->save();
        $conversation->addMessage(Message::SENDER_SYSTEM, 'Atendimento encerrado. Se precisar, é só escrever de novo por aqui.', ['kind' => 'closed']);
    }
}
