<?php

use App\Models\ScheduledPost;
use App\Models\SocialAccount;
use App\Models\Tenant;
use App\Models\User;

/**
 * Fix 2026-08-11: ScheduledPostController::store parou de converter scheduled_at
 * pra UTC. Como app.timezone = America/Sao_Paulo em prod, ao ler o Carbon
 * interpretava a string UTC como se fosse BRT — display e cron ficavam 3h off.
 *
 * Estes testes garantem que o input em BRT chega no DB sem shift de fuso.
 * Nota: phpunit.xml faz APP_TIMEZONE=UTC, entao aqui a gente verifica a string
 * bruta gravada (nao o Carbon reintepretado).
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function spWith(callable $work): void
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);
    $work($tenant, $user);
}

it('store grava scheduled_at sem converter pra UTC (input BRT vira BRT bruto no DB)', function () {
    spWith(function ($tenant, $user) {
        // Data futura arbitraria — 60 dias a frente pra nao brigar com "after:now"
        $futureBrt = now('America/Sao_Paulo')->addDays(60)->setTime(15, 0, 0);
        $input     = $futureBrt->format('Y-m-d\TH:i');

        $account = SocialAccount::create([
            'tenant_id'    => $tenant->id,
            'user_id'      => $user->id,
            'platform'     => 'facebook',
            'page_id'      => 'p1',
            'page_name'    => 'P',
            'access_token' => 'tok',
            'is_active'    => true,
        ]);

        $resp = $this->actingAs($user)->post('/social/posts', [
            'social_account_id' => $account->id,
            'platform'          => 'facebook',
            'caption'           => 'Teste tz',
            'scheduled_at'      => $input,
        ]);

        $resp->assertRedirect();

        $post = ScheduledPost::withoutGlobalScopes()->latest('id')->first();
        expect($post)->not->toBeNull();

        // Vai pro DB como "Y-m-d H:i:s" EXATAMENTE igual ao input em BRT.
        // Antes do fix ficava $futureBrt + 3h (convertido pra UTC).
        $rawStored = \DB::table('scheduled_posts')
            ->where('id', $post->id)
            ->value('scheduled_at');

        expect($rawStored)->toBe($futureBrt->format('Y-m-d H:i:s'));
    });
});

it('update tambem grava sem shift pra UTC', function () {
    spWith(function ($tenant, $user) {
        $account = SocialAccount::create([
            'tenant_id'    => $tenant->id,
            'user_id'      => $user->id,
            'platform'     => 'facebook',
            'page_id'      => 'p1',
            'page_name'    => 'Pagina Teste',
            'access_token' => 'tok',
            'is_active'    => true,
        ]);

        $original = now('America/Sao_Paulo')->addDays(30)->setTime(10, 0, 0);
        $post = ScheduledPost::create([
            'tenant_id'         => $tenant->id,
            'social_account_id' => $account->id,
            'user_id'           => $user->id,
            'platform'          => 'facebook',
            'caption'           => 'antes',
            'scheduled_at'      => $original,
            'status'            => 'scheduled',
        ]);

        $newTime = now('America/Sao_Paulo')->addDays(45)->setTime(20, 30, 0);
        $resp = $this->actingAs($user)->put('/social/posts/' . $post->id, [
            'platform'     => 'facebook',
            'caption'      => 'depois',
            'scheduled_at' => $newTime->format('Y-m-d\TH:i'),
        ]);

        $resp->assertRedirect();

        $rawStored = \DB::table('scheduled_posts')->where('id', $post->id)->value('scheduled_at');
        expect($rawStored)->toBe($newTime->format('Y-m-d H:i:s'));
    });
});

it('publish_now nao faz mais shift pra UTC (grava now() sem conversao)', function () {
    spWith(function ($tenant, $user) {
        // publish_now requer account vinculada (schema tem NOT NULL em social_account_id).
        // Mockamos MetaSocialPublisherService pra evitar chamada real na Meta API.
        $account = SocialAccount::create([
            'tenant_id'    => $tenant->id,
            'user_id'      => $user->id,
            'platform'     => 'facebook',
            'page_id'      => 'p1',
            'page_name'    => 'P',
            'access_token' => 'tok',
            'is_active'    => true,
        ]);

        $this->mock(\App\Services\MetaSocialPublisherService::class, function ($m) {
            $m->shouldReceive('publish')->andReturn(true);
        });

        $resp = $this->actingAs($user)->post('/social/posts', [
            'social_account_id' => $account->id,
            'platform'          => 'facebook',
            'caption'           => 'agora',
            'publish_now'       => '1',
        ]);

        $resp->assertRedirect();

        $post = ScheduledPost::withoutGlobalScopes()->latest('id')->first();
        $rawStored = \DB::table('scheduled_posts')->where('id', $post->id)->value('scheduled_at');
        // Antes: gravava now()->utc() (3h a mais no BRT). Agora grava now() direto.
        $diff = abs(now()->diffInSeconds(\Carbon\Carbon::parse($rawStored)));
        expect($diff)->toBeLessThan(60);
    });
});

it('posts:fix-timezone --dry-run nao mexe em nada', function () {
    spWith(function ($tenant, $user) {
        $account = SocialAccount::create([
            'tenant_id'    => $tenant->id,
            'user_id'      => $user->id,
            'platform'     => 'facebook',
            'page_id'      => 'p1',
            'page_name'    => 'P',
            'access_token' => 'tok',
            'is_active'    => true,
        ]);
        $orig = now()->addDays(10);
        $post = ScheduledPost::create([
            'tenant_id'         => $tenant->id,
            'social_account_id' => $account->id,
            'user_id'           => $user->id,
            'platform'          => 'facebook',
            'caption'           => 'buggy',
            'scheduled_at'      => $orig,
            'status'            => 'scheduled',
        ]);

        $exit = \Artisan::call('posts:fix-timezone', ['--dry-run' => true]);
        expect($exit)->toBe(0);

        // Nada mudou no DB nem gravou flag
        $rawStored = \DB::table('scheduled_posts')->where('id', $post->id)->value('scheduled_at');
        expect($rawStored)->toBe($orig->format('Y-m-d H:i:s'));
        expect(\App\Models\SystemSetting::getValue('posts_timezone_backfill_done'))->toBeNull();
    });
});

it('posts:fix-timezone --confirm subtrai 3h e marca a flag', function () {
    spWith(function ($tenant, $user) {
        $account = SocialAccount::create([
            'tenant_id'    => $tenant->id,
            'user_id'      => $user->id,
            'platform'     => 'facebook',
            'page_id'      => 'p1',
            'page_name'    => 'P',
            'access_token' => 'tok',
            'is_active'    => true,
        ]);
        $orig = now()->addDays(10)->setTime(18, 0, 0); // "18:00" que era pra ser 15:00
        $post = ScheduledPost::create([
            'tenant_id'         => $tenant->id,
            'social_account_id' => $account->id,
            'user_id'           => $user->id,
            'platform'          => 'facebook',
            'caption'           => 'buggy',
            'scheduled_at'      => $orig,
            'status'            => 'scheduled',
        ]);

        \Artisan::call('posts:fix-timezone', ['--confirm' => true]);

        $rawStored = \DB::table('scheduled_posts')->where('id', $post->id)->value('scheduled_at');
        $expected = $orig->copy()->subHours(3)->format('Y-m-d H:i:s');
        expect($rawStored)->toBe($expected);

        expect(\App\Models\SystemSetting::getValue('posts_timezone_backfill_done'))->not->toBeNull();

        // Idempotencia: rodar de novo sem --force nao mexe
        \Artisan::call('posts:fix-timezone', ['--confirm' => true]);
        $rawStored2 = \DB::table('scheduled_posts')->where('id', $post->id)->value('scheduled_at');
        expect($rawStored2)->toBe($expected);
    });
});
