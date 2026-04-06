<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ngo_grant_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('ngo_grant_id');

            $table->string('title');
            $table->enum('type', ['edital', 'plano_trabalho', 'anexo', 'comprovante', 'outros'])->default('outros');
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'ngo_grant_id']);
            // FK is optional in some environments, but we keep it for integrity.
            $table->foreign('ngo_grant_id')->references('id')->on('ngo_grants')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ngo_grant_documents');
    }
};

