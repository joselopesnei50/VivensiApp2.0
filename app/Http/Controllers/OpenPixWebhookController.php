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
