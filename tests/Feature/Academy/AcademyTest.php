<?php

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeAcademyUser(string $role = 'common'): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => $role]);
}

function makeCourse(bool $active = true, string $title = 'Curso Teste'): Course
{
    return Course::create([
        'title'        => $title,
        'slug'         => \Illuminate\Support\Str::slug($title . '-' . uniqid()),
        'description'  => 'Curso de teste',
        'teacher_name' => 'Professor X',
        'is_active'    => $active,
    ]);
}

function makeModuleWithLessons(Course $course, int $lessonCount = 3): array
{
    $module = Module::create([
        'course_id' => $course->id,
        'title'     => 'Modulo 1',
        'order'     => 1,
    ]);

    $lessons = [];
    for ($i = 1; $i <= $lessonCount; $i++) {
        $lessons[] = Lesson::create([
            'module_id'        => $module->id,
            'title'            => "Aula {$i}",
            'video_url'        => "https://example.com/v{$i}.mp4",
            'duration_minutes' => 10,
            'type'             => 'video',
            'order'            => $i,
        ]);
    }

    return [$module, $lessons];
}

// ── UX bug fix: ?lesson_id=X deve ser honrado ────────────────────────────────

it('show honra ?lesson_id=X quando aula pertence ao curso', function () {
    $user   = makeAcademyUser();
    $course = makeCourse();
    [$m, $lessons] = makeModuleWithLessons($course, 3);
    $this->actingAs($user);

    // Sem lesson_id: abre a primeira nao concluida (id 1)
    $r = $this->get("/academy/{$course->slug}");
    $r->assertOk();
    expect($r->viewData('currentLesson')->id)->toBe($lessons[0]->id);

    // Com lesson_id=X: abre a aula X (BUG anterior: ignorava e sempre abria a primeira)
    $r = $this->get("/academy/{$course->slug}?lesson_id={$lessons[2]->id}");
    $r->assertOk();
    expect($r->viewData('currentLesson')->id)->toBe($lessons[2]->id);
});

it('show ignora ?lesson_id de aula que NAO pertence ao curso (anti-IDOR)', function () {
    $user    = makeAcademyUser();
    $courseA = makeCourse(true, 'Curso A');
    $courseB = makeCourse(true, 'Curso B');
    [, $aulasA] = makeModuleWithLessons($courseA, 2);
    [, $aulasB] = makeModuleWithLessons($courseB, 2);
    $this->actingAs($user);

    // Tenta abrir aula do curso B na URL do curso A — deve cair no fallback
    $r = $this->get("/academy/{$courseA->slug}?lesson_id={$aulasB[0]->id}");
    $r->assertOk();
    expect($r->viewData('currentLesson')->id)->toBe($aulasA[0]->id);
});

// ── N+1 confirmado corrigido ─────────────────────────────────────────────────

it('index nao explode em N+1 mesmo com muitos cursos e aulas', function () {
    $user = makeAcademyUser();
    // 5 cursos x 2 modulos x 3 aulas = 30 aulas
    for ($c = 0; $c < 5; $c++) {
        $course = makeCourse(true, "Curso {$c}");
        for ($m = 0; $m < 2; $m++) {
            $mod = Module::create(['course_id' => $course->id, 'title' => "M{$m}", 'order' => $m]);
            for ($l = 0; $l < 3; $l++) {
                Lesson::create([
                    'module_id' => $mod->id, 'title' => "L{$l}",
                    'video_url' => 'x', 'duration_minutes' => 5, 'type' => 'video', 'order' => $l,
                ]);
            }
        }
    }
    $this->actingAs($user);

    DB::enableQueryLog();
    $this->get('/academy')->assertOk();
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Antes: ~5 (courses) + 5*2 (modules) + 5*2*3 (lessons users check) = 45+
    // Agora com eager loading: ~5-10 queries independente do numero de aulas.
    expect($queries)->toBeLessThan(20);
});

// ── markLessonAsViewed: guardas de is_active + rate limit ────────────────────

it('markLessonAsViewed aceita aula de curso ativo e cria pivot', function () {
    $user   = makeAcademyUser();
    $course = makeCourse(true);
    [, $lessons] = makeModuleWithLessons($course, 2);
    $this->actingAs($user);

    $r = $this->postJson("/academy/lessons/{$lessons[0]->id}/complete");
    $r->assertOk()->assertJson(['success' => true]);

    expect(DB::table('lesson_user')
        ->where('lesson_id', $lessons[0]->id)
        ->where('user_id', $user->id)
        ->whereNotNull('finished_at')
        ->exists()
    )->toBeTrue();
});

it('markLessonAsViewed bloqueia aula de curso INATIVO com 404', function () {
    $user   = makeAcademyUser();
    $course = makeCourse(false); // inativo
    [, $lessons] = makeModuleWithLessons($course, 1);
    $this->actingAs($user);

    $r = $this->postJson("/academy/lessons/{$lessons[0]->id}/complete");
    $r->assertStatus(404);
    expect(DB::table('lesson_user')->where('lesson_id', $lessons[0]->id)->count())->toBe(0);
});

it('markLessonAsViewed e idempotente (chamar 2x nao duplica linha)', function () {
    $user   = makeAcademyUser();
    $course = makeCourse();
    [, $lessons] = makeModuleWithLessons($course, 1);
    $this->actingAs($user);

    $this->postJson("/academy/lessons/{$lessons[0]->id}/complete")->assertOk();
    $this->postJson("/academy/lessons/{$lessons[0]->id}/complete")->assertOk();

    expect(DB::table('lesson_user')
        ->where('lesson_id', $lessons[0]->id)
        ->where('user_id', $user->id)
        ->count()
    )->toBe(1);
});

// ── Certificado ──────────────────────────────────────────────────────────────

it('completar 100% do curso gera certificado unico', function () {
    $user   = makeAcademyUser();
    $course = makeCourse();
    [, $lessons] = makeModuleWithLessons($course, 3);
    $this->actingAs($user);

    foreach ($lessons as $l) {
        $r = $this->postJson("/academy/lessons/{$l->id}/complete");
        $r->assertOk();
    }

    $cert = Certificate::where('user_id', $user->id)->where('course_id', $course->id)->first();
    expect($cert)->not->toBeNull();
    expect(strlen($cert->code))->toBe(16); // codigo agora tem 16 chars (era 10)
});

it('completar aula quando ja completou tudo NAO gera 2 certificados', function () {
    $user   = makeAcademyUser();
    $course = makeCourse();
    [, $lessons] = makeModuleWithLessons($course, 2);
    $this->actingAs($user);

    foreach ($lessons as $l) {
        $this->postJson("/academy/lessons/{$l->id}/complete");
    }
    // Re-marca a mesma aula
    $this->postJson("/academy/lessons/{$lessons[0]->id}/complete");

    expect(Certificate::where('user_id', $user->id)->count())->toBe(1);
});

it('downloadCertificate bloqueia user que nao e dono', function () {
    $dono    = makeAcademyUser();
    $outro   = makeAcademyUser();
    $course  = makeCourse();
    $cert = Certificate::create([
        'user_id' => $dono->id, 'course_id' => $course->id,
        'code' => 'ABCDEF1234567890', 'issued_at' => now(),
    ]);
    $this->actingAs($outro);

    $r = $this->get("/academy/certificate/{$cert->code}");
    $r->assertStatus(403);
});

it('downloadCertificate 404 se code invalido', function () {
    $user = makeAcademyUser();
    $this->actingAs($user);
    $r = $this->get('/academy/certificate/INEXISTENTE1234567');
    $r->assertStatus(404);
});
