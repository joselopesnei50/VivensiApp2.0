<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Last 10 Chats ===\n";
$chats = \App\Models\WhatsappChat::orderBy('last_message_at', 'desc')->take(10)->get();
foreach ($chats as $c) {
    echo "ID:{$c->id} Tenant:{$c->tenant_id} WA_ID:{$c->wa_id} Phone:{$c->contact_phone} Last:{$c->last_message_at}\n";
}

echo "\n=== Last 20 Messages ===\n";
$msgs = \App\Models\WhatsappMessage::orderBy('created_at', 'desc')->take(20)->get();
foreach ($msgs as $m) {
    echo "ID:{$m->id} Chat:{$m->chat_id} Dir:{$m->direction} Status:{$m->status} Content:" . mb_substr($m->content, 0, 30) . "...\n";
}
