<?php

namespace App\Services\Messaging;

use App\Models\User;
use App\Models\WhatsappChat;
use App\Models\WhatsappForm;
use App\Models\WhatsappFormAnswer;
use App\Models\WhatsappFormQuestion;
use App\Models\WhatsappFormSession;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * WhatsappFormEngine — Fase 4 (item 2.5 do roadmap).
 *
 * Máquina de estados conversacional. Despachado pelo webhook quando há
 * sessão ativa pro chat: valida a resposta, persiste e devolve a próxima
 * pergunta (ou marca completed). Sessão é única por chat — abrir uma
 * segunda fecha a anterior como 'abandoned'.
 *
 * O envio efetivo das perguntas pela Evolution API fica a cargo do
 * caller — esse Service só decide qual é a próxima e formata o conteúdo
 * (texto + payload de buttons/list quando aplicável).
 *
 * Vinculação ao lead (Fase 5) fica como reserva no campo lead_id; aqui o
 * Service nunca preenche.
 */
class WhatsappFormEngine
{
    /**
     * Inicia uma sessão num chat. Se houver uma anterior in_progress, é
     * marcada como 'abandoned' antes (atendente decidiu trocar de form).
     */
    public function start(WhatsappChat $chat, WhatsappForm $form, ?User $actor = null): WhatsappFormSession
    {
        if ($chat->tenant_id !== $form->tenant_id) {
            throw new RuntimeException('Form e chat de tenants diferentes.');
        }
        if (!$form->is_active) {
            throw new RuntimeException("Formulário '{$form->name}' está inativo.");
        }

        return DB::transaction(function () use ($chat, $form, $actor) {
            WhatsappFormSession::where('chat_id', $chat->id)
                ->where('status', WhatsappFormSession::STATUS_IN_PROGRESS)
                ->update([
                    'status'       => WhatsappFormSession::STATUS_ABANDONED,
                    'completed_at' => now(),
                ]);

            $firstQuestion = $form->questions()->orderBy('position')->first();
            if ($firstQuestion === null) {
                throw new RuntimeException("Formulário '{$form->name}' não tem perguntas.");
            }

            return WhatsappFormSession::create([
                'tenant_id'           => $form->tenant_id,
                'chat_id'             => $chat->id,
                'form_id'             => $form->id,
                'current_question_id' => $firstQuestion->id,
                'started_by'          => $actor?->id,
                'status'              => WhatsappFormSession::STATUS_IN_PROGRESS,
                'started_at'          => now(),
            ]);
        });
    }

    /**
     * Recupera sessão ativa do chat, ou null. Chamado pelo webhook ANTES
     * do fluxo normal de IA.
     */
    public function activeSessionFor(WhatsappChat $chat): ?WhatsappFormSession
    {
        return WhatsappFormSession::where('chat_id', $chat->id)
            ->where('status', WhatsappFormSession::STATUS_IN_PROGRESS)
            ->with('currentQuestion')
            ->first();
    }

    /**
     * Processa uma resposta inbound. Valida, persiste e avança o cursor.
     *
     * Retorno: [
     *   'next_question' => ?WhatsappFormQuestion (null = sessão completa),
     *   'session'       => WhatsappFormSession (estado atualizado),
     *   'error'         => ?string (mensagem amigável quando a resposta é inválida),
     * ]
     *
     * @param array<string,mixed> $rawPayload payload original (button id, list selection, etc)
     */
    public function processInbound(
        WhatsappFormSession $session,
        string $userText,
        array $rawPayload = []
    ): array {
        if (!$session->isActive()) {
            return [
                'next_question' => null,
                'session'       => $session,
                'error'         => 'Sessão não está mais ativa.',
            ];
        }

        $current = $session->currentQuestion;
        if ($current === null) {
            return $this->complete($session);
        }

        $validation = $this->validateAnswer($current, $userText);
        if ($validation !== null) {
            return [
                'next_question' => $current, // repete a mesma pergunta
                'session'       => $session,
                'error'         => $validation,
            ];
        }

        return DB::transaction(function () use ($session, $current, $userText, $rawPayload) {
            WhatsappFormAnswer::updateOrCreate(
                ['session_id' => $session->id, 'question_id' => $current->id],
                [
                    'field_key'   => $current->field_key,
                    'answer_text' => $this->normalizeAnswer($current, $userText),
                    'raw_payload' => $rawPayload ?: null,
                ]
            );

            $next = WhatsappFormQuestion::where('form_id', $session->form_id)
                ->where('position', '>', $current->position)
                ->orderBy('position')
                ->first();

            if ($next === null) {
                $session->update([
                    'status'              => WhatsappFormSession::STATUS_COMPLETED,
                    'current_question_id' => null,
                    'completed_at'        => now(),
                ]);
                return [
                    'next_question' => null,
                    'session'       => $session->fresh('answers'),
                    'error'         => null,
                ];
            }

            $session->update(['current_question_id' => $next->id]);
            return [
                'next_question' => $next,
                'session'       => $session->fresh(),
                'error'         => null,
            ];
        });
    }

    public function cancel(WhatsappFormSession $session, ?User $actor = null): WhatsappFormSession
    {
        if (!$session->isActive()) {
            return $session;
        }
        $session->update([
            'status'       => WhatsappFormSession::STATUS_CANCELLED,
            'completed_at' => now(),
        ]);
        return $session->fresh();
    }

    /**
     * Devolve o payload pronto pra enviar pela Evolution API.
     *
     * @return array{text:string,buttons:?array,list:?array}
     */
    public function renderQuestion(WhatsappFormQuestion $question): array
    {
        $payload = [
            'text'    => $question->text,
            'buttons' => null,
            'list'    => null,
        ];

        $options = $question->options ?? [];
        if ($question->type === 'buttons' && is_array($options) && $options !== []) {
            $payload['buttons'] = array_slice($options, 0, 3); // Evolution limita a 3
        } elseif ($question->type === 'list' && is_array($options) && $options !== []) {
            $payload['list'] = $options;
        } elseif ($question->type === 'yes_no') {
            $payload['buttons'] = [
                ['id' => 'yes', 'label' => 'Sim'],
                ['id' => 'no',  'label' => 'Não'],
            ];
        }

        return $payload;
    }

    // ── Validação por tipo ─────────────────────────────────────────────────

    public function validateAnswer(WhatsappFormQuestion $q, string $userText): ?string
    {
        $text = trim($userText);

        if ($q->required && $text === '') {
            return 'A resposta é obrigatória. Tente novamente.';
        }
        if (!$q->required && $text === '') {
            return null;
        }

        switch ($q->type) {
            case 'number':
                if (!is_numeric($text)) {
                    return 'Por favor, responda com um número.';
                }
                $num = (int) $text;
                if ($q->min_value !== null && $num < $q->min_value) {
                    return "O valor mínimo é {$q->min_value}.";
                }
                if ($q->max_value !== null && $num > $q->max_value) {
                    return "O valor máximo é {$q->max_value}.";
                }
                return null;

            case 'yes_no':
                if (!in_array(mb_strtolower($text), ['sim', 'nao', 'não', 'yes', 'no'], true)) {
                    return 'Por favor, responda "sim" ou "não".';
                }
                return null;

            case 'buttons':
            case 'list':
                $options = $q->options ?? [];
                if (!is_array($options) || $options === []) {
                    return null; // sem options, aceita texto livre
                }
                $valid = array_map(fn ($o) => mb_strtolower((string) ($o['label'] ?? '')), $options);
                if (!in_array(mb_strtolower($text), $valid, true)) {
                    return 'Por favor, escolha uma das opções apresentadas.';
                }
                return null;

            case 'text':
            default:
                if ($q->validation_regex && @preg_match('/' . $q->validation_regex . '/u', $text) !== 1) {
                    return 'Resposta não corresponde ao formato esperado.';
                }
                return null;
        }
    }

    /**
     * Normaliza para persistência (lowercase em yes_no etc) e mantém texto
     * original como fonte da verdade.
     */
    public function normalizeAnswer(WhatsappFormQuestion $q, string $userText): string
    {
        $text = trim($userText);
        if ($q->type === 'yes_no') {
            $lower = mb_strtolower($text);
            if (in_array($lower, ['sim', 'yes'], true))  return 'sim';
            if (in_array($lower, ['nao', 'não', 'no'], true)) return 'nao';
        }
        return $text;
    }

    /**
     * Renderiza a pergunta como texto plano pronto pra Evolution::sendMessage.
     * Como hoje o serviço só manda texto, opções aparecem numeradas no corpo
     * e a validação aceita o LABEL. Quando a Evolution suportar buttons/list
     * nativos, esse método pode evoluir mantendo o contrato.
     */
    public function renderQuestionAsText(WhatsappFormQuestion $question): string
    {
        $payload = $this->renderQuestion($question);
        $linhas  = [$payload['text']];

        if (is_array($payload['buttons']) && $payload['buttons'] !== []) {
            $linhas[] = '';
            foreach ($payload['buttons'] as $i => $btn) {
                $linhas[] = ($i + 1) . '. ' . (string) ($btn['label'] ?? '');
            }
            $linhas[] = '';
            $linhas[] = '_Responda com o número ou o texto da opção._';
        } elseif (is_array($payload['list']) && $payload['list'] !== []) {
            $linhas[] = '';
            foreach ($payload['list'] as $opt) {
                $linhas[] = '• ' . (string) ($opt['label'] ?? '');
            }
            $linhas[] = '';
            $linhas[] = '_Responda com o texto exato da opção._';
        }

        return implode("\n", $linhas);
    }

    /**
     * Resolve uma resposta inbound que veio como "1" / "2" para o label
     * correspondente. Mantém texto original quando não bater.
     */
    public function resolveButtonShortcut(WhatsappFormQuestion $question, string $userText): string
    {
        $payload = $this->renderQuestion($question);
        $options = $payload['buttons'] ?? $payload['list'] ?? null;
        if (!is_array($options) || $options === []) {
            return $userText;
        }
        $trim = trim($userText);
        if (!ctype_digit($trim)) {
            return $userText;
        }
        $idx = ((int) $trim) - 1;
        if ($idx < 0 || $idx >= count($options)) {
            return $userText;
        }
        return (string) ($options[$idx]['label'] ?? $userText);
    }

    /**
     * Atalho quando o caller só quer fechar a sessão sem nova resposta.
     */
    public function complete(WhatsappFormSession $session): array
    {
        $session->update([
            'status'              => WhatsappFormSession::STATUS_COMPLETED,
            'current_question_id' => null,
            'completed_at'        => now(),
        ]);
        return [
            'next_question' => null,
            'session'       => $session->fresh('answers'),
            'error'         => null,
        ];
    }
}
