<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PagSeguroService
{
    protected $email;
    protected $token;
    protected $baseUrl;

    public function __construct()
    {
        // Try to get from System Settings (Database) first, then fallback to Config (.env)
        $this->email = SystemSetting::getValue('pagseguro_email') ?? config('services.pagseguro.email');
        $this->token = SystemSetting::getValue('pagseguro_token') ?? config('services.pagseguro.token');
        
        $env = SystemSetting::getValue('pagseguro_environment') ?? config('services.pagseguro.environment');
        
        $this->baseUrl = $env === 'sandbox' 
            ? 'https://ws.sandbox.pagseguro.uol.com.br' 
            : 'https://ws.pagseguro.uol.com.br';
    }

    /**
     * Check a notification code to get transaction details.
     */
    public function checkNotification($notificationCode)
    {
        if (!$notificationCode) {
            return null;
        }

        try {
            $response = Http::get("{$this->baseUrl}/v3/transactions/notifications/{$notificationCode}", [
                'email' => $this->email,
                'token' => $this->token,
            ]);

            if ($response->successful()) {
                $xml = simplexml_load_string($response->body());
                return json_decode(json_encode($xml), true);
            }
            
            Log::error('PagSeguro Notification Check Failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('PagSeguro Service Error', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create a specific payment request (Checkout).
     * 
     * @param array $data Transaction data (amount, description, reference, items, sender)
     * @return array|null ['code' => '...', 'date' => '...', 'paymentLink' => '...']
     */
    public function createPayment(array $data)
    {
        // Build XML Payload for PagSeguro Checkout
        // Documentation: https://dev.pagseguro.uol.com.br/reference/checkout-pagseguro-criacao-checkout-padrao
        
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><checkout/>');
        $xml->addChild('currency', 'BRL');
        
        // Items
        $items = $xml->addChild('items');
        $item = $items->addChild('item');
        $item->addChild('id', '1');
        $item->addChild('description', substr($data['description'] ?? 'Pagamento Vivensi', 0, 100));
        $item->addChild('amount', number_format($data['amount'], 2, '.', ''));
        $item->addChild('quantity', '1');
        $item->addChild('weight', '0');

        // Redirect URL (Where the user goes after payment)
        $xml->addChild('redirectURL', 'https://vivensi.app.br/dashboard');

        // Reference (Our Transaction ID or External ID)
        $xml->addChild('reference', $data['reference']);

        // Sender (Customer)
        if (isset($data['sender'])) {
            $sender = $xml->addChild('sender');
            $sender->addChild('name', $data['sender']['name']);
            $sender->addChild('email', $data['sender']['email']);
            // Standard sandbox email if needed for testing:
            // $sender->addChild('email', 'c47864878482485994868478@sandbox.pagseguro.com.br'); 
            
            if (isset($data['sender']['phone'])) {
                $phone = $sender->addChild('phone');
                $phone->addChild('areaCode', substr($data['sender']['phone'], 0, 2));
                $phone->addChild('number', substr($data['sender']['phone'], 2));
            }
            
            // CPF is required
            if (isset($data['sender']['cpf'])) {
                 $documents = $sender->addChild('documents');
                 $document = $documents->addChild('document');
                 $document->addChild('type', 'CPF');
                 $document->addChild('value', $data['sender']['cpf']);
            }
        }

        // Shipping (Optional - address required for some methods)
        // For digital goods, we can set type=3 (Not specified)
        $shipping = $xml->addChild('shipping');
        $shipping->addChild('type', '3');

        // Sanitize and Prepare XML
        $xmlString = $xml->asXML();
        $xmlString = trim($xmlString); // Remove potential whitespace/BOM

        // Log the payload for debugging
        Log::info('PagSeguro Payload', ['xml' => $xmlString]);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/xml; charset=UTF-8'
            ])->withBody($xmlString, 'application/xml')
              ->post("{$this->baseUrl}/v2/checkout?email={$this->email}&token={$this->token}");

            if ($response->successful()) {
                $resXml = simplexml_load_string($response->body());
                $resArr = json_decode(json_encode($resXml), true);
                
                // Construct payment link (Sandbox or Production)
                $host = config('services.pagseguro.environment') === 'sandbox' 
                    ? 'sandbox.pagseguro.uol.com.br' 
                    : 'pagseguro.uol.com.br';
                
                $resArr['paymentLink'] = "https://{$host}/v2/checkout/payment.html?code=" . $resArr['code'];
                
                return $resArr;
            }

            Log::error('PagSeguro Create Payment Failed', [
                'status' => $response->status(),
                'body' => $response->body() // Often contains XML error details
            ]);
            
            return null;

        } catch (\Exception $e) {
            Log::error('PagSeguro Create Payment Exception', ['msg' => $e->getMessage()]);
            return null;
        }
    }
}
