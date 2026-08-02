<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Anti-reuso do link público de chamada.
 *
 * Regra: uma mesma pessoa não pode marcar presença 2x na mesma sessão.
 * Constraint em DB fecha o caminho contra race condition + defesa em
 * profundidade sobre o updateOrCreate do PublicAttendanceController.
 *
 * Chave: (class_session_id, project_person_id) — sem ip_hash porque
 * escolas atrás de NAT compartilham IP público (bloquearia legítimos).
 * A dedup de ProjectPerson em modo aberta é feita no controller via
 * firstOrCreate por (name lowered, project, tenant) — combinado com
 * essa constraint, garante que 1 pessoa = 1 marcação por sessão.
 *
 * Se já existe duplicata (bug antigo em modo aberta que criava
 * multiple PPs), aborta com mensagem clara antes de aplicar unique.
 */
return new class extends Migration {
    public function up(): void
    {
        $duplicates = DB::table('class_attendances')
            ->select('class_session_id', 'project_person_id', DB::raw('COUNT(*) as n'))
            ->groupBy('class_session_id', 'project_person_id')
            ->havingRaw('COUNT(*) > 1')
            ->limit(5)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $samples = $duplicates->map(fn ($d) => "session={$d->class_session_id}/person={$d->project_person_id}({$d->n}x)")->implode(', ');
            throw new \RuntimeException(
                "Migration abortada: existem attendances duplicadas em class_attendances. Amostra: {$samples}. "
                . "Resolva mantendo o attendance mais antigo por (session,person) antes de aplicar o unique."
            );
        }

        Schema::table('class_attendances', function (Blueprint $table) {
            $table->unique(
                ['class_session_id', 'project_person_id'],
                'class_attendances_session_person_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('class_attendances', function (Blueprint $table) {
            $table->dropUnique('class_attendances_session_person_unique');
        });
    }
};
