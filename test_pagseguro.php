<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Services\PagSeguroService;
use Illuminate\Support\Facades\Log;

// Mock Data
$data = [
    'reference' => 'TEST_REF_' . time(),
    'amount' => 10.00,
    'description' => 'Teste de Pagamento CLI',
    'sender' => [
        'name' => 'Comprador Teste',
        'email' => 'c47864878482485994868478@sandbox.pagseguro.com.br', // Sandbox User
        'cpf' => '11111111111', // Dummy CPF
        'phone' => '11999999999',
    ]
];

echo "Iniciando teste de Checkout PagSeguro...\n";
echo "Ambiente: " . config('services.pagseguro.environment') . "\n";
echo "Email Config: " . config('services.pagseguro.email') . "\n";

try {
    $service = new PagSeguroService();
    $result = $service->createPayment($data);

    if ($result) {
        echo "\n[SUCESSO] Pagamento Criado!\n";
        echo "Payment Link: " . $result['paymentLink'] . "\n";
        echo "Code: " . $result['code'] . "\n";
    } else {
        echo "\n[ERRO] Falha ao criar pagamento. Verifique os logs.\n";
    }
} catch (\Exception $e) {
    echo "\n[ERRO] Falha ao criar pagamento.\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getResponse') && $e->getResponse()) {
        echo "Resposta do PagSeguro: " . $e->getResponse()->getBody()->getContents() . "\n";
    }
    exit(1);
} catch (\Throwable $e) {
    echo "\n[EXCEPTION] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
