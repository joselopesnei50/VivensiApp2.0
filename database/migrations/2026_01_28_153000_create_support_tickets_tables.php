<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable(); // Removed constraint for safety
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Who opened it
            $table->string('subject');
            $table->string('category')->default('general'); // Tech, Billing, etc
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->enum('status', ['open', 'answered_by_admin', 'answered_by_user', 'closed'])->default('open');
            $table->timestamps();
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Who sent the message
            $table->text('message');
            $table->string('attachment_path')->nullable();
            $table->boolean('is_admin_reply')->default(false); // Helper to quickly check if it's from support side
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_tickets');
    }
};
