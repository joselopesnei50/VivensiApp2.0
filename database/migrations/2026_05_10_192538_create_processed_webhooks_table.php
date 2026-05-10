<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('processed_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 50);
            $table->string('webhook_id', 255);
            $table->string('event', 100);
            $table->timestamp('processed_at')->useCurrent();
            $table->unique(['gateway', 'webhook_id'], 'uq_gateway_webhook');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_webhooks');
    }
};
