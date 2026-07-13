<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Matricula de um ProjectPerson em uma ProjectClass (Turma).
 *
 * Pivot com atributos proprios: status (ativo/saiu/concluido), datas
 * enrolled_at/unenrolled_at, notas. Unique (project_class_id, project_person_id)
 * garante que o mesmo aluno so tem 1 matricula por turma.
 */
class ProjectClassEnrollment extends Pivot
{
    use HasFactory, BelongsToTenant;

    public $incrementing = true;
    protected $table     = 'project_class_enrollments';

    public const STATUS_ATIVO     = 'ativo';
    public const STATUS_SAIU      = 'saiu';
    public const STATUS_CONCLUIDO = 'concluido';

    protected $fillable = [
        'tenant_id',
        'project_class_id',
        'project_person_id',
        'enrolled_at',
        'unenrolled_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'enrolled_at'   => 'datetime',
        'unenrolled_at' => 'datetime',
    ];

    public function projectClass(): BelongsTo
    {
        return $this->belongsTo(ProjectClass::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(ProjectPerson::class, 'project_person_id');
    }
}
