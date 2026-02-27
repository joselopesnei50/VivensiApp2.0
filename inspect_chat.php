<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel;
$app->make(Kernel::class)->bootstrap();

use App\Models\WhatsappChat;
use Illuminate\Support\Facades\DB;

$phone = '250624394436784';

echo "Searching for chats related to: $phone\n";

$chats = WhatsappChat::where('contact_phone', 'LIKE', "%$phone%")
    ->orWhere('wa_id', 'LIKE', "%$phone%")
    ->get();

if ($chats->isEmpty()) {
    echo "No chats found.\n";
} else {
    foreach ($chats as $chat) {
        echo "ID: {$chat->id} | Tenant: {$chat->tenant_id} | WA_ID: '{$chat->wa_id}' | Phone: '{$chat->contact_phone}' | Status: {$chat->status}\n";
    }
}
