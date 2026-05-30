<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_chats', function (Blueprint $table) {
            $table->string('opt_in_source', 50)->nullable()->after('opt_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_chats', function (Blueprint $table) {
            $table->dropColumn('opt_in_source');
        });
    }
};
