<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'code',
        'issued_at',
        'student_name',
        'course_title',
        'teacher_name',
        'workload_minutes',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function studentName(): string
    {
        return trim($this->student_name ?: ($this->user->name ?? 'Aluno'));
    }

    public function courseTitle(): string
    {
        return trim($this->course_title ?: ($this->course->title ?? 'Curso'));
    }

    public function teacherName(): string
    {
        return trim($this->teacher_name ?: ($this->course->teacher_name ?? '')) ?: 'Direção Pedagógica';
    }

    /** "8 horas", "1 hora e 30 minutos", "45 minutos" — null se não houver duração cadastrada. */
    public function workloadLabel(): ?string
    {
        $min = (int) $this->workload_minutes;
        if ($min <= 0) {
            return null;
        }
        $h = intdiv($min, 60);
        $m = $min % 60;
        $parts = [];
        if ($h) $parts[] = $h . ($h === 1 ? ' hora' : ' horas');
        if ($m) $parts[] = $m . ' minutos';
        return implode(' e ', $parts);
    }

    /** Código como aparece no PDF: XXXX-XXXX-XXXX-XXXX. */
    public function formattedCode(): string
    {
        return trim(chunk_split($this->code, 4, '-'), '-');
    }

    /** Aceita o código digitado com hífens, espaços ou minúsculas. */
    public static function normalizeCode(?string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
