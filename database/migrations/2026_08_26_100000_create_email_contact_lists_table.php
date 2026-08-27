<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Listas de contatos pra campanhas de email — reutilizaveis entre campanhas.
 * Cliente sobe CSV UMA vez, dispara varias vezes.
 *
 * opt_in_confirmed = declaração LGPD art. 7º inciso IX (legítimo interesse):
 * cliente afirma que possui consentimento dos contatos. Sem isso, dispara
 * bloqueia. Auditoria em created_by + created_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_contact_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->json('tags')->nullable()->comment('Etiquetas livres pra organizar: doadores, ex-alunos, etc');
            $table->boolean('opt_in_confirmed')->default(false)->comment('LGPD art 7 IX — cliente declarou consentimento');
            $table->timestamp('opt_in_confirmed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_contact_lists');
    }
};
