<?php
/**
 * Diagnostico: testa acesso ao Together AI pra geracao de imagem.
 * Roda: php scripts/test-together-image.php
 *
 * Tenta 3 modelos (FLUX schnell Free, FLUX 1.1 pro, SDXL) e mostra
 * status+body de cada — assim identificamos se a conta tem acesso a
 * algum modelo serverless ou se e problema de billing.
 */
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$key = \App\Models\SystemSetting::getValue('together_ai_api_key');
if (!$key) {
    echo "ERRO: together_ai_api_key nao configurado\n";
    exit(1);
}

$models = [
    'stabilityai/stable-diffusion-xl-base-1.0',
    'black-forest-labs/FLUX.1-schnell-Free',
    'black-forest-labs/FLUX.1-schnell',
];

foreach ($models as $model) {
    echo str_repeat('=', 60) . "\n";
    echo "MODEL: {$model}\n";

    $ch = curl_init('https://api.together.xyz/v1/images/generations');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode([
            'model'  => $model,
            'prompt' => 'a red apple on a wooden table',
            'width'  => 512,
            'height' => 512,
            'n'      => 1,
            'steps'  => 4,
        ]),
        CURLOPT_TIMEOUT        => 30,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP {$code}\n";
    echo "Body: " . mb_substr((string) $body, 0, 500) . "\n\n";
}
