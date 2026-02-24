<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\WhatsappChat;
use App\Models\WhatsappMessage;
use App\Models\WhatsappAuditLog;
use App\Models\WhatsappConfig;
use App\Services\EvolutionApiService;

echo "=== WhatsApp Identity Cleanup Script ===\n";

// 1. Find the reachable JID for José Lopes Nei (from the sender log we saw: 5516997618695@s.whatsapp.net)
// We'll search for chats that look like duplicates of this person.
$searchName = 'José Lopes Nei';
$chats = WhatsappChat::where('contact_name', 'LIKE', "%{$searchName}%")->get();

echo "Found " . count($chats) . " chats for '{$searchName}'\n";

$targetChat = null;
$others = [];

foreach ($chats as $c) {
    echo "Processing Chat ID:{$c->id} | WA_ID:{$c->wa_id} | Phone:{$c->contact_phone}\n";
    // The target chat should be the one with the real phone number if possible
    if (str_contains($c->wa_id, '@s.whatsapp.net') || (strlen($c->wa_id) > 10 && !str_contains($c->wa_id, '@lid'))) {
        if (!$targetChat || $c->last_message_at > $targetChat->last_message_at) {
            if ($targetChat) $others[] = $targetChat;
            $targetChat = $c;
        } else {
            $others[] = $c;
        }
    } else {
        $others[] = $c;
    }
}

if (!$targetChat && count($chats) > 0) {
    $targetChat = $chats[0];
    $others = array_slice($chats->all(), 1);
}

if ($targetChat) {
    echo "Target Standard Chat ID: {$targetChat->id}\n";
    
    // Standardize target wa_id if it's not a full JID
    if (!str_contains($targetChat->wa_id, '@')) {
        $targetChat->wa_id = $targetChat->wa_id . '@s.whatsapp.net';
        $targetChat->save();
        echo "Standardized Target WA_ID to: {$targetChat->wa_id}\n";
    }

    foreach ($others as $dupe) {
        echo "Merging duplicate Chat ID:{$dupe->id} into {$targetChat->id}...\n";
        
        // Move messages
        $msgCount = WhatsappMessage::where('chat_id', $dupe->id)->update(['chat_id' => $targetChat->id]);
        echo "  - Moved {$msgCount} messages.\n";
        
        // Move logs
        WhatsappAuditLog::where('chat_id', $dupe->id)->update(['chat_id' => $targetChat->id]);
        
        // Update timestamps if newer
        if ($dupe->last_message_at > $targetChat->last_message_at) {
            $targetChat->update(['last_message_at' => $dupe->last_message_at]);
        }
        
        $dupe->delete();
        echo "  - Duplicate deleted.\n";
    }
}

echo "\nDone. Running general cleanup for other duplicates...\n";

/**
 * 2. Automatic Bot Identity Cleanup
 * Deleta chats criados acidentalmente para o próprio número da instância (SaaS safe).
 */
$configs = WhatsappConfig::with('tenant')->get();
foreach ($configs as $config) {
    $evo = new EvolutionApiService($config->tenant);
    $botJid = $evo->getBotJid();
    
    if ($botJid) {
        $botNumber = explode('@', $botJid)[0];
        echo "Cleaning up self-chats for Tenant: {$config->tenant_id} | Bot: {$botJid}\n";
        
        $botChats = WhatsappChat::where('tenant_id', $config->tenant_id)
            ->where(function($q) use ($botJid, $botNumber) {
                $q->where('wa_id', $botJid)
                  ->orWhere('wa_id', $botNumber)
                  ->orWhere('contact_phone', $botNumber);
            })
            ->get();

        foreach ($botChats as $bc) {
            echo "Deleting self-chat ID:{$bc->id}\n";
            WhatsappMessage::where('chat_id', $bc->id)->delete();
            WhatsappAuditLog::where('chat_id', $bc->id)->delete();
            $bc->delete();
        }
    }
}

/**
 * 3. General Duplicate Merge
 * Une conversas duplicadas (LID/JID) para cada tenant.
 */
$allChats = WhatsappChat::all();
$seenPhones = [];

foreach ($allChats as $chat) {
    $phone = $chat->contact_phone;
    if (empty($phone)) {
        $phone = explode('@', $chat->wa_id)[0];
    }
    if (empty($phone) || strlen($phone) < 8 || !is_numeric($phone)) continue;
    
    $tenantKey = $chat->tenant_id . '_' . $phone;
    
    if (isset($seenPhones[$tenantKey])) {
        $originalId = $seenPhones[$tenantKey];
        $original = WhatsappChat::find($originalId);
        
        if ($original) {
            echo "Merging Duplicate for Tenant {$chat->tenant_id} | Phone {$phone} (ID:{$chat->id} -> ID:{$original->id})\n";
            WhatsappMessage::where('chat_id', $chat->id)->update(['chat_id' => $original->id]);
            WhatsappAuditLog::where('chat_id', $chat->id)->update(['chat_id' => $original->id]);
            $chat->delete();
        }
    } else {
        $seenPhones[$tenantKey] = $chat->id;
    }
}

echo "\nCleanup finished. All systems restored.\n";
