<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_contact_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('email', 255);
            $table->string('name', 200)->nullable();
            $table->enum('status', ['active', 'bounced', 'unsubscribed', 'invalid'])->default('active')->index();
            $table->enum('source', ['csv_upload', 'manual', 'api'])->default('csv_upload');
            $table->timestamp('added_at')->useCurrent();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            // 1 email por lista (nao dup dentro da mesma lista, mas pode aparecer em varias)
            $table->unique(['email_contact_list_id', 'email']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_contacts');
    }
};
