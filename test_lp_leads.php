<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LandingPage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

echo "--- LANDING PAGE LEAD FUNNEL TEST ---\n";

// 1. Setup Page
$page = LandingPage::first();
if (!$page) {
    $page = LandingPage::create([
        'tenant_id' => 1,
        'title' => 'Página de Teste Audit',
        'slug' => 'teste-audit-' . rand(100, 999),
        'status' => 'published'
    ]);
}

echo "Testing Page: {$page->title} (slug: {$page->slug})\n";

// 2. Submit Lead
$url = url("/lp/{$page->slug}/lead");
if (strpos($url, 'public') === false) {
    $url = "http://localhost/vivensi-laravel/public/lp/{$page->slug}/lead"; 
}

echo "Submitting lead to: $url\n";

$user = App\Models\User::where('tenant_id', 1)->first();
Auth::login($user);
$session = session()->all();

$payload = [
    'name' => 'João Auditor',
    'email' => 'joao@audit.com',
    'phone' => '11999999999',
    'interesses' => 'Doação Mensal',
    'mensagem' => 'Gostaria de participar dos testes.'
];

// We use withHeaders to mimic session if needed, but since we are in PHP script using Auth::login, 
// we might need to send the request from a stateful context or just hit the DB via controller directly.
// For a TRUE funnel test, we'll try to use the Http client with cookies after a login.
// But easier here is to call the controller method or just fix the route visibility.
// Let's try the request with cookies.

$response = Http::asForm()
    ->withHeaders(['X-CSRF-TOKEN' => csrf_token()]) // If CSRF is enabled this might fail without session cookie
    ->post($url, $payload);

echo "Response Status: " . $response->status() . "\n";

// 3. Verify Database
$lead = DB::table('landing_page_leads')
          ->where('landing_page_id', $page->id)
          ->where('email', 'joao@audit.com')
          ->first();

if ($lead) {
    echo "\n[SUCCESS] Lead captured correctly in database!\n";
    echo "Extra Data: " . $lead->extra_data . "\n";
} else {
    echo "\n[FAILURE] Lead not found in database.\n";
}

echo "--- END TEST ---\n";
