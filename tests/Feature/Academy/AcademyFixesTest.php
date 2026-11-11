<?php

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Tenant;
use App\Models\User;
use App\Http\Middleware\RequireTwoFactor;
use Illuminate\Support\Facades\DB;

/*
| Correções e melhorias da Vivensi Academy (2026-11).
*/

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'super_admin', 'email' => 'sa_' . uniqid() . '@example.com']);
    $this->student = User::factory()->create([
        'tenant_id' => Tenant::factory()->create(['subscription_status' => 'active'])->id,
        'role' => 'ngo', 'name' => 'Ana Paula Souza', 'email' => 'aluna_' . uniqid() . '@example.com',
    ]);
    $this->asAdmin = fn () => $this->withoutMiddleware(RequireTwoFactor::class)->actingAs($this->admin);
});

function academyCourse(array $durations = [90, 60]): Course
{
    $c = Course::create(['title' => 'Captação de Recursos', 'slug' => 'captacao-' . uniqid(), 'is_active' => true, 'teacher_name' => 'Prof. Rui']);
    $m = Module::create(['course_id' => $c->id, 'title' => 'Módulo 1', 'order' => 1]);
    foreach ($durations as $i => $d) {
        Lesson::create(['module_id' => $m->id, 'title' => 'Aula ' . ($i + 1), 'type' => 'video',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ?t=' . ($i + 5), 'duration_minutes' => $d, 'order' => $i + 1]);
    }
    return $c;
}

it('libera YouTube, Vimeo e Google Maps na política de segurança', function () {
    $csp = $this->get('/')->headers->get('Content-Security-Policy');
    expect($csp)->toContain('frame-src')->toContain('https://www.youtube-nocookie.com')
        ->toContain('https://player.vimeo.com')->toContain('https://www.google.com');
});

it('o player aceita link curto do YouTube com tempo', function () {
    $c = academyCourse();
    $this->actingAs($this->student)->get('/academy/' . $c->slug)->assertOk()
        ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0&amp;modestbranding=1&amp;start=5', false);
});

it('dois cursos com o mesmo título ganham endereços diferentes, e editar o título não muda o endereço', function () {
    ($this->asAdmin)()->post(route('admin.academy.store'), ['title' => 'Gestão de Projetos', 'is_active' => 1])->assertRedirect();
    ($this->asAdmin)()->post(route('admin.academy.store'), ['title' => 'Gestão de Projetos', 'is_active' => 1])->assertRedirect();
    expect(Course::pluck('slug')->sort()->values()->all())->toBe(['gestao-de-projetos', 'gestao-de-projetos-2']);

    $c = Course::where('slug', 'gestao-de-projetos')->first();
    ($this->asAdmin)()->put(route('admin.academy.update', $c->id), ['title' => 'Gestão de Projetos Sociais', 'is_active' => 1]);
    expect($c->fresh()->slug)->toBe('gestao-de-projetos')->and($c->fresh()->title)->toBe('Gestão de Projetos Sociais');
});

it('certificado sai com a carga horária e continua válido depois de excluir o curso', function () {
    $c = academyCourse([90, 60]); // 2h30
    foreach (Lesson::whereHas('module', fn ($q) => $q->where('course_id', $c->id))->get() as $l) {
        $this->actingAs($this->student)->postJson("/academy/lessons/{$l->id}/complete")->assertOk();
    }
    $cert = Certificate::sole();
    expect($cert->workload_minutes)->toBe(150)
        ->and($cert->workloadLabel())->toBe('2 horas e 30 minutos')
        ->and($cert->course_title)->toBe('Captação de Recursos')
        ->and($cert->student_name)->toBe('Ana Paula Souza');

    $html = view('academy.certificate_pdf', ['certificate' => $cert->load('user', 'course')])->render();
    expect($html)->toContain('Carga horária')->toContain('2 horas e 30 minutos')->toContain('verificar-certificado/' . $cert->code);

    ($this->asAdmin)()->delete(route('admin.academy.destroy', $c->id))->assertRedirect();
    expect(Module::where('course_id', $c->id)->count())->toBe(0)
        ->and(Lesson::count())->toBe(0)
        ->and(DB::table('lesson_user')->count())->toBe(0)
        ->and(Certificate::count())->toBe(1);

    $this->actingAs($this->student)->get('/academy/meus-certificados')->assertOk()->assertSee('Captação de Recursos');
});

it('verificação pública: confere pelo código sem login e não expõe e-mail', function () {
    $cert = Certificate::create(['user_id' => $this->student->id, 'course_id' => academyCourse()->id, 'code' => 'ABCDEFGHJKLMNPQR',
        'issued_at' => '2026-11-10 10:00:00', 'student_name' => 'Ana Paula Souza', 'course_title' => 'Captação de Recursos', 'workload_minutes' => 120]);

    $this->get('/verificar-certificado')->assertOk()->assertSee('Verificar certificado');
    $this->get('/verificar-certificado/ABCDEFGHJKLMNPQR')->assertOk()
        ->assertSee('Certificado válido')->assertSee('Ana Paula Souza')->assertSee('Captação de Recursos')
        ->assertSee('2 horas')->assertSee('10/11/2026')->assertDontSee($this->student->email);
    // digitado com hífen e minúsculas, pelo formulário
    $this->get('/verificar-certificado?codigo=abcd-efgh-jklm-npqr')->assertRedirect('/verificar-certificado/ABCDEFGHJKLMNPQR');
    $this->get('/verificar-certificado/ZZZZZZZZZZZZZZZZ')->assertNotFound()->assertSee('Certificado não encontrado');
});

it('admin edita a aula: título, link, duração e ordem', function () {
    $c = academyCourse([30]);
    $l = Lesson::first();

    ($this->asAdmin)()->put(route('admin.academy.lessons.update', $l->id), [
        'title' => 'Aula revisada', 'type' => 'video', 'video_url' => 'https://vimeo.com/76979871', 'duration_minutes' => 45, 'order' => 3,
    ])->assertSessionHasNoErrors();
    expect($l->fresh()->only(['title', 'video_url', 'duration_minutes', 'order']))->toBe([
        'title' => 'Aula revisada', 'video_url' => 'https://vimeo.com/76979871', 'duration_minutes' => 45, 'order' => 3,
    ]);

    ($this->asAdmin)()->put(route('admin.academy.lessons.update', $l->id), ['title' => 'X', 'type' => 'video', 'video_url' => 'https://exemplo.com/video'])
        ->assertSessionHasErrors('video_url');
    ($this->asAdmin)()->put(route('admin.academy.lessons.update', $l->id), ['title' => 'X', 'type' => 'ebook'])
        ->assertSessionHasErrors('document');

    ($this->asAdmin)()->get(route('admin.academy.modules.index', $c->id))->assertOk()->assertSee('editLesson' . $l->id, false);
});
