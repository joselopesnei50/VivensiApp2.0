<?php

namespace App\Services\Bruno;

use App\Jobs\SendMeetingEmailsJob;
use App\Models\MeetingBooking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Ferramentas que o Bot Vendedor "Bruno" pode executar via function calling
 * do DeepSeek. Por enquanto: consulta de slots e criação de agendamento.
 *
 * Convenção: cada tool retorna array com 'success' bool + payload OU 'error' string.
 * O LLM recebe esse payload serializado e formula a próxima resposta.
 */
class BrunoTools
{
    /**
     * Definições no formato OpenAI / DeepSeek tools — vão direto no payload.
     */
    public static function definitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'consultar_slots',
                    'description' => 'Consulta os horários livres na agenda do Vivensi para uma data específica. Use quando o lead mencionar uma data que quer marcar reunião. Retorna lista de horários no formato HH:MM. Se a lista vier vazia, sugira outra data.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => [
                                'type' => 'string',
                                'description' => 'Data no formato YYYY-MM-DD (exemplo: 2026-07-03 para 03 de julho de 2026). Não pode ser data passada. Se o lead disser "amanhã", "quinta", "semana que vem" — converta voce mesmo pra YYYY-MM-DD considerando a data de hoje informada no system prompt.',
                            ],
                        ],
                        'required' => ['data'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'agendar_reuniao',
                    'description' => 'Confirma o agendamento de uma reunião de 20 minutos. SÓ chame depois que: (1) o lead escolheu data e hora dentre os slots disponíveis retornados por consultar_slots, e (2) você coletou nome completo, e-mail e telefone/WhatsApp do lead. Retorna token de cancelamento e link.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'nome'        => ['type' => 'string', 'description' => 'Nome completo do lead'],
                            'email'       => ['type' => 'string', 'description' => 'E-mail válido do lead'],
                            'telefone'    => ['type' => 'string', 'description' => 'Telefone ou WhatsApp do lead (com DDD)'],
                            'data'        => ['type' => 'string', 'description' => 'Data no formato YYYY-MM-DD'],
                            'hora'        => ['type' => 'string', 'description' => 'Horário no formato HH:MM (24h, ex: 14:00)'],
                            'observacoes' => ['type' => 'string', 'description' => 'Opcional. Notas sobre o interesse do lead (vertical, dor, plano de interesse).'],
                        ],
                        'required' => ['nome', 'email', 'telefone', 'data', 'hora'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Executa uma tool por nome com argumentos do LLM.
     */
    public static function execute(string $name, array $args): array
    {
        Log::info('BrunoTools: execute', ['name' => $name, 'args' => $args]);

        return match ($name) {
            'consultar_slots' => self::consultarSlots($args),
            'agendar_reuniao' => self::agendarReuniao($args),
            default           => ['error' => "Ferramenta desconhecida: {$name}"],
        };
    }

    private static function consultarSlots(array $args): array
    {
        $data = $args['data'] ?? null;
        if (!$data) {
            return ['error' => 'Campo data é obrigatório (formato YYYY-MM-DD)'];
        }

        try {
            $dia = Carbon::parse($data);
        } catch (\Throwable $e) {
            return ['error' => 'Data inválida. Use formato YYYY-MM-DD.'];
        }

        if ($dia->isPast() && !$dia->isToday()) {
            return ['error' => 'Não é possível consultar data no passado. Escolha outra data.'];
        }

        $slots = MeetingBooking::availableSlotsFor($data);

        return [
            'success'        => true,
            'data'           => $data,
            'data_formatada' => $dia->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY'),
            'slots'          => $slots,
            'total'          => count($slots),
        ];
    }

    private static function agendarReuniao(array $args): array
    {
        foreach (['nome', 'email', 'telefone', 'data', 'hora'] as $f) {
            if (empty($args[$f])) {
                return ['error' => "Campo obrigatório ausente: {$f}"];
            }
        }

        if (!filter_var($args['email'], FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'E-mail inválido. Peça novamente ao lead.'];
        }

        $available = MeetingBooking::availableSlotsFor($args['data']);
        if (!in_array($args['hora'], $available, true)) {
            return [
                'error'    => 'Horário não está mais disponível',
                'available' => $available,
            ];
        }

        try {
            $booking = MeetingBooking::create([
                'name'         => $args['nome'],
                'email'        => $args['email'],
                'phone'        => $args['telefone'],
                'notes'        => $args['observacoes'] ?? null,
                'meeting_date' => $args['data'],
                'meeting_time' => $args['hora'],
                'status'       => 'confirmed',
            ]);
        } catch (\Throwable $e) {
            Log::error('BrunoTools: agendar_reuniao falhou', ['err' => $e->getMessage()]);
            return ['error' => 'Não foi possível salvar o agendamento. Tente novamente.'];
        }

        SendMeetingEmailsJob::dispatch($booking->id);

        return [
            'success'        => true,
            'token'          => $booking->confirmation_token,
            'data_formatada' => Carbon::parse($booking->meeting_date)->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY'),
            'hora'           => $args['hora'],
            'link_cancelar'  => url('/agendar/cancelar/' . $booking->confirmation_token),
        ];
    }
}
