<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;

echo "🎓 Criando cursos demo para Vivensi Academy...\n\n";

// Curso 1: Gestão Financeira para ONGs
$course1 = Course::create([
    'title' => 'Gestão Financeira para ONGs',
    'slug' => 'gestao-financeira-ongs',
    'description' => '<p>Aprenda a gerenciar as finanças da sua organização de forma profissional e transparente.</p><p>Este curso aborda desde o básico de contabilidade até estratégias avançadas de captação de recursos.</p>',
    'teacher_name' => 'Prof. Maria Silva',
    'thumbnail_url' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=800',
    'is_active' => true,
]);

$mod1 = Module::create(['course_id' => $course1->id, 'title' => 'Introdução à Gestão Financeira', 'order' => 1]);
Lesson::create(['module_id' => $mod1->id, 'title' => 'Boas-vindas ao Curso', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'duration_minutes' => 5, 'type' => 'video', 'order' => 1]);
Lesson::create(['module_id' => $mod1->id, 'title' => 'Conceitos Básicos', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'duration_minutes' => 15, 'type' => 'video', 'order' => 2]);

$mod2 = Module::create(['course_id' => $course1->id, 'title' => 'Planejamento Orçamentário', 'order' => 2]);
Lesson::create(['module_id' => $mod2->id, 'title' => 'Como Criar um Orçamento', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'duration_minutes' => 20, 'type' => 'video', 'order' => 1]);
Lesson::create(['module_id' => $mod2->id, 'title' => 'Controle de Despesas', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'duration_minutes' => 18, 'type' => 'video', 'order' => 2]);

echo "✅ Curso criado: {$course1->title}\n";

// Curso 2: Captação de Recursos
$course2 = Course::create([
    'title' => 'Captação de Recursos e Editais',
    'slug' => 'captacao-recursos-editais',
    'description' => '<p>Domine as técnicas de captação de recursos e aprenda a escrever projetos vencedores para editais.</p><p>Inclui templates e cases reais de sucesso.</p>',
    'teacher_name' => 'Dr. João Santos',
    'thumbnail_url' => 'https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?w=800',
    'is_active' => true,
]);

$mod3 = Module::create(['course_id' => $course2->id, 'title' => 'Fundamentos da Captação', 'order' => 1]);
Lesson::create(['module_id' => $mod3->id, 'title' => 'Introdução à Captação', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'duration_minutes' => 10, 'type' => 'video', 'order' => 1]);
Lesson::create(['module_id' => $mod3->id, 'title' => 'Tipos de Financiadores', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'duration_minutes' => 12, 'type' => 'video', 'order' => 2]);

$mod4 = Module::create(['course_id' => $course2->id, 'title' => 'Escrevendo Projetos', 'order' => 2]);
Lesson::create(['module_id' => $mod4->id, 'title' => 'Estrutura de um Projeto', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'duration_minutes' => 25, 'type' => 'video', 'order' => 1]);
Lesson::create(['module_id' => $mod4->id, 'title' => 'Orçamento do Projeto', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'duration_minutes' => 20, 'type' => 'video', 'order' => 2]);

echo "✅ Curso criado: {$course2->title}\n";

// Curso 3: Gestão de Projetos Sociais
$course3 = Course::create([
    'title' => 'Gestão de Projetos Sociais',
    'slug' => 'gestao-projetos-sociais',
    'description' => '<p>Aprenda metodologias ágeis aplicadas ao terceiro setor.</p><p>Gerencie seus projetos com eficiência e impacto mensurável.</p>',
    'teacher_name' => 'Ana Paula Costa',
    'thumbnail_url' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=800',
    'is_active' => true,
]);

$mod5 = Module::create(['course_id' => $course3->id, 'title' => 'Metodologias Ágeis', 'order' => 1]);
Lesson::create(['module_id' => $mod5->id, 'title' => 'O que é Scrum?', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'duration_minutes' => 15, 'type' => 'video', 'order' => 1]);
Lesson::create(['module_id' => $mod5->id, 'title' => 'Kanban para ONGs', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'duration_minutes' => 12, 'type' => 'video', 'order' => 2]);

$mod6 = Module::create(['course_id' => $course3->id, 'title' => 'Medindo Impacto', 'order' => 2]);
Lesson::create(['module_id' => $mod6->id, 'title' => 'Indicadores de Impacto', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'duration_minutes' => 18, 'type' => 'video', 'order' => 1]);
Lesson::create(['module_id' => $mod6->id, 'title' => 'Relatórios de Resultados', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'duration_minutes' => 22, 'type' => 'video', 'order' => 2]);

echo "✅ Curso criado: {$course3->title}\n";

// Curso 4: Marketing Digital para ONGs
$course4 = Course::create([
    'title' => 'Marketing Digital para o Terceiro Setor',
    'slug' => 'marketing-digital-ongs',
    'description' => '<p>Amplifique o alcance da sua causa nas redes sociais.</p><p>Estratégias práticas de comunicação e engajamento digital.</p>',
    'teacher_name' => 'Carlos Mendes',
    'thumbnail_url' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800',
    'is_active' => true,
]);

$mod7 = Module::create(['course_id' => $course4->id, 'title' => 'Redes Sociais', 'order' => 1]);
Lesson::create(['module_id' => $mod7->id, 'title' => 'Estratégia de Conteúdo', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'duration_minutes' => 14, 'type' => 'video', 'order' => 1]);
Lesson::create(['module_id' => $mod7->id, 'title' => 'Instagram para Causas', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'duration_minutes' => 16, 'type' => 'video', 'order' => 2]);

echo "✅ Curso criado: {$course4->title}\n";

echo "\n==================================\n";
echo "✅ 4 CURSOS DEMO CRIADOS!\n";
echo "==================================\n";
echo "Total de módulos: 7\n";
echo "Total de aulas: 14\n\n";
echo "Acesse: /academy\n";
