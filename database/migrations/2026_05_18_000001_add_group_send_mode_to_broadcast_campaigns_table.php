<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            // 'group'   = mensagem enviada para o chat do grupo
            // 'members' = mensagem privada para cada membro individualmente
            $table->string('group_send_mode', 10)->default('group')->after('group_ids');
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->dropColumn('group_send_mode');
        });
    }
};
