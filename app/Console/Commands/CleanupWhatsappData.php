<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupWhatsappData extends Command
{
    protected $signature = 'whatsapp:cleanup {--days=365 : Delete messages older than N days}';
    protected $description = 'Cleanup old WhatsApp messages/notes to support data retention policies.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $this->error('days must be >= 1');
            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $deletedMessages = DB::table('whatsapp_messages')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $deletedNotes = DB::table('whatsapp_notes')
            ->where('created_at', '<', $cutoff)
            ->delete();

        // Remove chats with no remaining messages and last_message_at older than cutoff
        $staleChatIds = DB::table('whatsapp_chats as c')
            ->leftJoin('whatsapp_messages as m', 'm.chat_id', '=', 'c.id')
            ->whereNull('m.id')
            ->whereNotNull('c.last_message_at')
            ->where('c.last_message_at', '<', $cutoff)
            ->select('c.id')
            ->limit(5000)
            ->pluck('id');

        $deletedChats = 0;
        if ($staleChatIds->count() > 0) {
            $deletedChats = DB::table('whatsapp_chats')->whereIn('id', $staleChatIds)->delete();
        }

        $this->info("Deleted messages: {$deletedMessages}");
        $this->info("Deleted notes: {$deletedNotes}");
        $this->info("Deleted empty chats: {$deletedChats}");

        return self::SUCCESS;
    }
}

