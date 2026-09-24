<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['product', 'service'])->default('service')->index();
            $table->string('sku', 60)->nullable();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->string('unit', 20)->default('un');
            $table->string('category', 60)->nullable()->index();
            $table->integer('stock_quantity')->nullable();
            $table->boolean('track_stock')->default(false);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku'], 'catalog_products_tenant_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_products');
    }
};
