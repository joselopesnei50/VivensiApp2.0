<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_labels', function (Blueprint $table) {
            $table->string('slug', 150)->change();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_labels', function (Blueprint $table) {
            $table->string('slug', 40)->change();
        });
    }
};
