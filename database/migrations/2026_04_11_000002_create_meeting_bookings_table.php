<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->date('meeting_date');
            $table->time('meeting_time');
            $table->string('status')->default('confirmed'); // confirmed, cancelled
            $table->string('confirmation_token', 64)->unique();
            $table->timestamps();

            $table->index(['meeting_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_bookings');
    }
};
