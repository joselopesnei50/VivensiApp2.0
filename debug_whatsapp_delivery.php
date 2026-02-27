<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel;
$app->make(Kernel::class)->bootstrap();

use App\Models\WhatsappMessage;
use App\Models\WhatsappAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

header('Content-Type: text/plain');

echo "--- WHATSAPP DELIVERY DIAGNOSTICS ---\n\n";

// 1. Check Outbound Messages
echo "RECENT OUTBOUND MESSAGES (Last 10):\n";
try {
    $messages = WhatsappMessage::where('direction', 'outbound')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    foreach ($messages as $msg) {
        echo "[{$msg->created_at}] ID: {$msg->message_id} | Chat: {$msg->chat_id} | Content: " . substr($msg->content, 0, 50) . "...\n";
    }
} catch (\Exception $e) {
    echo "Error fetching messages: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Check Audit Logs
echo "RECENT AUDIT LOGS (Last 10):\n";
try {
    $logs = WhatsappAuditLog::orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    foreach ($logs as $log) {
        $details = is_array($log->details) ? json_encode($log->details) : $log->details;
        echo "[{$log->created_at}] Event: {$log->event} | Actor: {$log->actor_type} | Details: {$details}\n";
    }
} catch (\Exception $e) {
    echo "Error fetching audit logs: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Check Laravel Log for WhatsApp keywords
echo "RECENT LARAVEL LOG ENTRIES (WhatsApp AI):\n";
$logPath = storage_path('logs/laravel.log');
if (File::exists($logPath)) {
    $lines = file($logPath);
    $count = 0;
    $maxLines = 50;
    // Walk backwards
    for ($i = count($lines) - 1; $i >= 0 && $count < $maxLines; $i--) {
        if (str_contains($lines[$i], 'WhatsApp') || str_contains($lines[$i], 'Evolution')) {
            echo $lines[$i];
            $count++;
        }
    }
} else {
    echo "Log file not found at: {$logPath}\n";
}

echo "\n--- END OF DIAGNOSTICS ---\n";
