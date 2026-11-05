<?php

use App\Models\Post;
use App\Models\PostView;
use App\Models\User;
use App\Services\BlogMetricsService;
use App\Support\TrafficClassifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Métricas do blog: robôs separados de pessoas, origem, dispositivo,
 * comparação com o período anterior e as páginas do super admin.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

const BM_CHROME  = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';
const BM_IPHONE  = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';
const BM_ANDROID = 'Mozilla/5.0 (Linux; Android 14; SM-S911B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36';
const BM_IPAD    = 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

function bmAdmin(): User
{
    $u = User::factory()->create(['role' => 'super_admin', 'two_factor_confirmed_at' => now()]);
    session(['2fa_verified' => true]);
    return $u;
}

function bmPost(string $title = 'Post'): Post
{
    return Post::create([
        'title' => $title, 'slug' => \Illuminate\Support\Str::slug($title) . '-' . uniqid(),
        'content' => '<p>x</p>', 'is_published' => true, 'published_at' => now()->subDays(3),
    ]);
}

function bmView(Post $post, string $ip, $when, string $ua = BM_CHROME, ?string $referer = null): void
{
    DB::table('post_views')->insert([
        'post_id' => $post->id, 'ip_address' => $ip, 'user_agent' => $ua, 'referer' => $referer,
        'is_bot' => TrafficClassifier::isBot($ua), 'source' => TrafficClassifier::source($referer, 'vivensi.app.br'),
        'device' => TrafficClassifier::device($ua), 'created_at' => $when,
    ]);
}

it('separa robôs de navegadores de verdade', function () {
    foreach ([
        'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'WhatsApp/2.23.20.0 A',
        'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
        'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)',
        'curl/8.4.0',
        'python-requests/2.31.0',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/120.0 Safari/537.36',
        '',
        null,
    ] as $ua) {
        expect(TrafficClassifier::isBot($ua))->toBeTrue("deveria ser robô: {$ua}");
    }

    foreach ([BM_CHROME, BM_IPHONE, BM_ANDROID, BM_IPAD,
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Instagram 305.0.0',
    ] as $ua) {
        expect(TrafficClassifier::isBot($ua))->toBeFalse("deveria ser pessoa: {$ua}");
    }
});

it('identifica dispositivo', function () {
    expect(TrafficClassifier::device(BM_IPHONE))->toBe('mobile')
        ->and(TrafficClassifier::device(BM_ANDROID))->toBe('mobile')
        ->and(TrafficClassifier::device(BM_IPAD))->toBe('tablet')
        ->and(TrafficClassifier::device('Mozilla/5.0 (Linux; Android 13; SM-X200) AppleWebKit/537.36 Chrome/120 Safari/537.36'))->toBe('tablet')
        ->and(TrafficClassifier::device(BM_CHROME))->toBe('desktop');
});

it('identifica a origem pelo referer', function () {
    $own = 'vivensi.app.br';
    expect(TrafficClassifier::source(null, $own))->toBe('direct')
        ->and(TrafficClassifier::source('https://www.google.com.br/', $own))->toBe('google')
        ->and(TrafficClassifier::source('https://www.bing.com/search?q=x', $own))->toBe('search')
        ->and(TrafficClassifier::source('https://l.instagram.com/?u=x', $own))->toBe('instagram')
        ->and(TrafficClassifier::source('https://lm.facebook.com/l.php', $own))->toBe('facebook')
        ->and(TrafficClassifier::source('https://www.linkedin.com/feed/', $own))->toBe('linkedin')
        ->and(TrafficClassifier::source('https://t.co/abc', $own))->toBe('x')
        ->and(TrafficClassifier::source('https://mail.google.com/mail/u/0/', $own))->toBe('email')
        ->and(TrafficClassifier::source('https://chatgpt.com/', $own))->toBe('ai')
        ->and(TrafficClassifier::source('https://vivensi.app.br/blog', $own))->toBe('internal')
        ->and(TrafficClassifier::source('https://www.vivensi.app.br/', $own))->toBe('internal')
        ->and(TrafficClassifier::source('https://portalong.org/artigo', $own))->toBe('other');
});

it('registro da visita grava classificação e mantém robô marcado', function () {
    $post = bmPost();
    $req = Request::create('/blog/x', 'GET', [], [], [], [
        'REMOTE_ADDR' => '10.0.0.1', 'HTTP_USER_AGENT' => BM_IPHONE, 'HTTP_REFERER' => 'https://l.instagram.com/',
    ]);
    PostView::record($post, $req);

    $bot = Request::create('/blog/x', 'GET', [], [], [], [
        'REMOTE_ADDR' => '10.0.0.2', 'HTTP_USER_AGENT' => 'WhatsApp/2.23.20.0 A',
    ]);
    PostView::record($post, $bot);

    $rows = DB::table('post_views')->orderBy('id')->get();
    expect($rows)->toHaveCount(2)
        ->and((bool) $rows[0]->is_bot)->toBeFalse()
        ->and($rows[0]->source)->toBe('instagram')
        ->and($rows[0]->device)->toBe('mobile')
        ->and((bool) $rows[1]->is_bot)->toBeTrue();
});

it('migração reclassifica o histórico', function () {
    $post = bmPost();
    $migration = require database_path('migrations/2026_11_05_120001_add_classification_to_post_views.php');
    $migration->down();

    DB::table('post_views')->insert([
        ['post_id' => $post->id, 'ip_address' => '1.1.1.1', 'user_agent' => BM_ANDROID, 'referer' => 'https://www.google.com/', 'created_at' => now()],
        ['post_id' => $post->id, 'ip_address' => '2.2.2.2', 'user_agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)', 'referer' => null, 'created_at' => now()],
    ]);

    $migration->up();

    $rows = DB::table('post_views')->orderBy('id')->get();
    expect((bool) $rows[0]->is_bot)->toBeFalse()
        ->and($rows[0]->source)->toBe('google')
        ->and($rows[0]->device)->toBe('mobile')
        ->and((bool) $rows[1]->is_bot)->toBeTrue()
        ->and($rows[1]->source)->toBe('direct');
});

it('resumo conta só pessoas e compara com o período anterior', function () {
    $this->travelTo(now()->setTime(12, 0));
    $a = bmPost('Post A');
    $b = bmPost('Post B');

    // período atual (7 dias): A = 3 visitas (2 leitores), B = 1
    bmView($a, '1.1.1.1', now()->subDays(1), BM_IPHONE, 'https://www.google.com/');
    bmView($a, '1.1.1.1', now()->subDays(2), BM_IPHONE, 'https://www.google.com/');
    bmView($a, '2.2.2.2', now(), BM_CHROME);
    bmView($b, '3.3.3.3', now()->subDays(6), BM_CHROME, 'https://l.instagram.com/');
    // robôs no período: não contam
    bmView($a, '9.9.9.9', now(), 'Mozilla/5.0 (compatible; Googlebot/2.1)');
    bmView($b, '9.9.9.8', now(), 'WhatsApp/2.23.20.0 A');
    // período anterior: A = 1, B = 0
    bmView($a, '4.4.4.4', now()->subDays(10), BM_CHROME);
    // fora dos dois períodos
    bmView($b, '5.5.5.5', now()->subDays(40), BM_CHROME);

    $m = app(BlogMetricsService::class)->summary(7);

    expect($m['views'])->toBe(4)
        ->and($m['prev_views'])->toBe(1)
        ->and($m['change'])->toBe(300.0)
        ->and($m['readers'])->toBe(3)
        ->and($m['bots'])->toBe(2)
        ->and($m['all_time'])->toBe(6)
        ->and($m['daily']['values'])->toHaveCount(7)
        ->and(array_sum($m['daily']['values']))->toBe(4)
        ->and(end($m['daily']['values']))->toBe(1);

    $sources = collect($m['sources'])->pluck('count', 'key')->all();
    expect($sources)->toEqualCanonicalizing(['google' => 2, 'direct' => 1, 'instagram' => 1])->and(array_key_first($sources))->toBe('google');

    $devices = collect($m['devices'])->pluck('count', 'key')->all();
    expect($devices)->toEqualCanonicalizing(['mobile' => 2, 'desktop' => 2]);

    $top = $m['top_posts'];
    expect($top[0]['post']->id)->toBe($a->id)
        ->and($top[0]['views'])->toBe(3)
        ->and($top[0]['readers'])->toBe(2)
        ->and($top[0]['change'])->toBe(200.0)
        ->and($top[0]['top_source'])->toBe('Google')
        ->and($top[1]['change'])->toBeNull(); // B não tinha visita antes = "novo"

    // métricas de um post só
    $mb = app(BlogMetricsService::class)->summary(7, $b->id);
    expect($mb['views'])->toBe(1)->and($mb['bots'])->toBe(1)->and($mb['top_posts'])->toBe([]);

    // período inválido cai para 30 dias
    expect(app(BlogMetricsService::class)->summary(13)['days'])->toBe(30);
});

it('páginas de métricas abrem para o super admin', function () {
    $admin = bmAdmin();
    $post  = bmPost('Como captar recursos');
    bmView($post, '1.1.1.1', now(), BM_IPHONE, 'https://www.google.com/');
    bmView($post, '2.2.2.2', now(), BM_CHROME, 'https://portalong.org/x');

    $this->actingAs($admin)->get(route('admin.blog.metrics', ['dias' => 7]))
        ->assertOk()
        ->assertSee('Métricas do Blog')
        ->assertSee('Como captar recursos')
        ->assertSee('Google')
        ->assertSee('portalong.org');

    $this->actingAs($admin)->get(route('admin.blog.post_metrics', $post))
        ->assertOk()
        ->assertSee('Como captar recursos')
        ->assertSee('Desde a publicação');
});

it('usuário comum não acessa as métricas', function () {
    $user = User::factory()->create(['role' => 'manager']);
    $this->actingAs($user)->get(route('admin.blog.metrics'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.blog.post_metrics', bmPost()))->assertForbidden();
});

it('lista do Blog CMS mostra visitas sem robôs', function () {
    $admin = bmAdmin();
    $post  = bmPost('Post contado');
    bmView($post, '1.1.1.1', now(), BM_CHROME);
    bmView($post, '2.2.2.2', now(), BM_IPHONE);
    bmView($post, '9.9.9.9', now(), 'facebookexternalhit/1.1');

    $this->actingAs($admin)->get(route('admin.blog.index'))
        ->assertOk()
        ->assertViewHas('totalViews', 2)
        ->assertViewHas('viewsMonth', 2)
        ->assertSee(route('admin.blog.metrics'));
});
