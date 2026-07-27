<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Gera o PDF do e-book "Guia Completo do Modulo Beneficiarios" via DomPDF
 * e publica no Academy (Course + Module + Lesson).
 *
 * Idempotente: rodar 2x nao duplica curso — atualiza o PDF em disco e
 * re-registra a lesson apontando pro novo arquivo.
 *
 * Uso:  php artisan academy:publish-ebook-beneficiarios
 */
class PublishEbookBeneficiarios extends Command
{
    protected $signature = 'academy:publish-ebook-beneficiarios';
    protected $description = 'Gera o PDF do e-book Beneficiarios e publica no Academy';

    private const COURSE_SLUG = 'guia-modulo-beneficiarios';
    private const COURSE_TITLE = 'Guia Completo do Modulo Beneficiarios';
    private const MODULE_TITLE = 'Guia Completo';
    private const LESSON_TITLE = 'E-book — Modulo Beneficiarios (18 paginas)';
    private const PDF_PATH = 'academy/documents/ebook-modulo-beneficiarios.pdf';

    public function handle(): int
    {
        $this->info('Gerando PDF do e-book...');

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('academy.ebooks.beneficiarios');
            $pdf->setPaper('a4', 'portrait');
            Storage::disk('public')->put(self::PDF_PATH, $pdf->output());
        } catch (\Throwable $e) {
            $this->error('Falha ao gerar PDF: ' . $e->getMessage());
            return self::FAILURE;
        }

        $fullUrl = Storage::disk('public')->url(self::PDF_PATH);
        $this->info("PDF salvo em: storage/app/public/" . self::PDF_PATH);
        $this->info("URL publica: {$fullUrl}");

        $this->info('Publicando no Academy...');

        $course = Course::firstOrCreate(
            ['slug' => self::COURSE_SLUG],
            [
                'title'        => self::COURSE_TITLE,
                'description'  => 'Aprenda a cadastrar, acompanhar e relatar dados de beneficiarios da sua organizacao com seguranca e conformidade LGPD.',
                'teacher_name' => 'Equipe Vivensi',
                'is_active'    => true,
            ]
        );

        $module = Module::firstOrCreate(
            ['course_id' => $course->id, 'title' => self::MODULE_TITLE],
            ['order' => 1]
        );

        // Lesson: se ja existe (por titulo dentro do modulo), atualiza URL.
        $lesson = Lesson::where('module_id', $module->id)
            ->where('title', self::LESSON_TITLE)
            ->first();

        if ($lesson) {
            $lesson->update(['document_url' => $fullUrl, 'type' => 'ebook']);
            $action = 'atualizada';
        } else {
            Lesson::create([
                'module_id'        => $module->id,
                'title'            => self::LESSON_TITLE,
                'document_url'     => $fullUrl,
                'video_url'        => null,
                'duration_minutes' => 30, // tempo estimado de leitura
                'type'             => 'ebook',
                'order'            => 1,
            ]);
            $action = 'criada';
        }

        $this->newLine();
        $this->info("Curso: {$course->title} (slug: {$course->slug})");
        $this->info("Modulo: {$module->title}");
        $this->info("Aula {$action}: " . self::LESSON_TITLE);
        $this->newLine();
        $this->info('Pronto! Acesse /academy pra ver o e-book publicado.');

        return self::SUCCESS;
    }
}
