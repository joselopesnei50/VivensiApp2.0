<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            // Polimorfico — attachable_type + attachable_id apontam pro dono
            // (Asset, InventoryItem, InventoryMovement no primeiro momento).
            $table->string('attachable_type', 191);
            $table->unsignedBigInteger('attachable_id');
            $table->string('original_name');
            // Path relativo dentro do disk 'local' (private).
            // Ex: tenants/{tenantId}/attachments/{morphType}/{uuid}.{ext}
            $table->string('path');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['attachable_type', 'attachable_id'], 'attachments_morph_idx');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
