<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_data_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->string('facebook_user_id')->nullable()->index();
            $table->string('confirmation_code', 64)->unique();
            $table->enum('status', ['received', 'processing', 'completed', 'rejected'])->default('received');
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_data_deletion_requests');
    }
};
