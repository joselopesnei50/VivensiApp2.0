<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenPix\PhpSdk\Client;
use App\Models\RaffleTicket;
use App\Models\Raffle;
use App\Mail\RaffleTicketPaid;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * OpenPixWebhookController
 *
 * Rota: POST /openpix/webhook (publica, sem Auth).
 * Auth: HMAC validado pelo SDK oficial (OpenPix\PhpSdk\Client::webhooks).
 *
 * ESTADO ATUAL DA INTEGRACAO (auditoria 2026-06):
 * - Nenhum fluxo no codigo atual cria charges OpenPix (nao ha chamada
 *   ao SDK $openpix->charges em controllers/services/jobs). Portanto a
 *   integracao esta INCOMPLETA: este webhook nunca recebe eventos de
 *   verdade. O lookup RaffleTicket::where('transaction_id', $correlationID)
 *   nao casa hoje porque RaffleController::confirmPayment seta
 *   transaction_id = $transaction->id (PK auto-increment global),
 *   nao o correlationID UUID que a OpenPix devolveria.
 * - Se a integracao for ativada no futuro, sera necessario:
 *     1. Adicionar coluna semantica em raffle_tickets (ex.:
 *        openpix_correlation_id) ou prefixar/sobrescrever transaction_id
 *        com o correlationID retornado pela charge create.
 *     2. Trocar o lookup na linha ~68 desta classe para usar a nova
 *        coluna E adicionar where('tenant_id', $ticket_tenant_id) como
 *        defense-in-depth.
 *
 * ISOLAMENTO MULTI-TENANT:
 * - Rota publica nao aciona o filtro global de BelongsToTenant.
 * - Hoje o lookup RaffleTicket::where('transaction_id', ...) e seguro
 *   por colisao acidental porque correlationID nunca coincidira com
 *   um Transaction->id (formatos diferentes), mas isso e seguranca
 *   por desenho frouxo — depende do correlationID ser UUID-ish e nao
 *   numerico. Validar no momento de ativar.
 */
class OpenPixWebhookController extends Controller
{
    const SIGNATURE_HEADER = "x-webhook-signature";
    const OPENPIX_CHARGE_COMPLETED_EVENT = "OPENPIX:CHARGE_COMPLETED";

    public function __construct(private Client $openpix) {}

    public function receive(Request $request)
    {
        if ($response = $this->allowRequestOnlyFromOpenPix($request)) {
            return $response;
        }
        return $this->handleWebhook($request);
    }

    private function allowRequestOnlyFromOpenPix(Request $request)
    {
        $rawPayload = $request->getContent();
        $signature = $request->header(self::SIGNATURE_HEADER);

        try {
            $isValid = !empty($rawPayload) && !empty($signature) && $this->openpix->webhooks()->isWebhookValid($rawPayload, $signature);
            if ($isValid) {
                return null;
            }
        } catch (\Exception $e) {
            Log::error("OpenPix Webhook Validation Error: " . $e->getMessage());
        }

        return response()->json(["errors" => [["message" => "Invalid webhook signature."]]], 400);
    }

    private function handleWebhook(Request $request)
    {
        $event = $request->input("event");
        
        if ($event === self::OPENPIX_CHARGE_COMPLETED_EVENT) {
            return $this->handleChargePaid($request);
        }

        return response()->json(["message" => "Webhook received but not processed."]);
    }

    private function handleChargePaid(Request $request)
    {
        $correlationID = $request->input("charge.correlationID");

        if (!$correlationID) {
            return response()->json(["message" => "Missing correlationID."], 400);
        }

        $emailsToSend = [];

        DB::transaction(function () use ($correlationID, &$emailsToSend) {
            $tickets = RaffleTicket::where('transaction_id', $correlationID)
                ->where('status', '!=', 'paid')
                ->lockForUpdate()
                ->get();

            if ($tickets->isEmpty()) {
                Log::info("OpenPix Webhook: tickets já pagos ou não encontrados para correlationID: {$correlationID}");
                return;
            }

            foreach ($tickets as $ticket) {
                $ticket->update(['status' => 'paid']);
                $emailsToSend[] = $ticket;
            }
        });

        // Enviar emails fora da transaction para não bloquear
        foreach ($emailsToSend as $ticket) {
            try {
                Mail::to($ticket->buyer_email)->send(new RaffleTicketPaid($ticket->raffle, $ticket));
            } catch (\Exception $e) {
                Log::error("Error sending RaffleTicketPaid email: " . $e->getMessage());
            }
        }

        return response()->json(["message" => "Success."]);
    }
}
