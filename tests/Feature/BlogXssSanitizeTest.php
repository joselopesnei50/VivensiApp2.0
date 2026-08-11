<?php

use App\Models\Post;
use App\Models\User;

/**
 * Defense-in-depth 2026-08-11: BlogController::store/update passa content por
 * sanitize_user_html (HTMLPurifier). Render publico ja sanitiza, mas o purge
 * no gravacao bloqueia payloads antes de tocar o DB.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function bxsAdmin(): User
{
    $u = User::factory()->create([
        'role'                    => 'super_admin',
        'two_factor_confirmed_at' => now(),
    ]);
    // RequireTwoFactor middleware bloqueia super_admin sem sessao 2FA verificada.
    session(['2fa_verified' => true]);
    return $u;
}

it('store remove script tag do content antes de gravar', function () {
    $admin = bxsAdmin();

    $this->actingAs($admin)->post('/admin/blog', [
        'title'   => 'Post malicioso',
        'content' => '<p>Bom dia</p><script>alert(1)</script><p>fim</p>',
    ])->assertRedirect(route('admin.blog.index'));

    $post = Post::latest('id')->first();
    expect($post->content)->not->toContain('<script');
    expect($post->content)->not->toContain('alert(1)');
    expect($post->content)->toContain('Bom dia');
    expect($post->content)->toContain('fim');
});

it('store remove onclick e handlers inline', function () {
    $admin = bxsAdmin();

    $this->actingAs($admin)->post('/admin/blog', [
        'title'   => 'Post com handler',
        'content' => '<p onclick="steal()">clique</p><a href="javascript:evil()">link</a>',
    ])->assertRedirect();

    $post = Post::latest('id')->first();
    expect($post->content)->not->toContain('onclick');
    expect($post->content)->not->toContain('javascript:');
});

it('store mantem markup legitimo do editor Quill', function () {
    $admin = bxsAdmin();

    $html = '<h2>Titulo</h2><p><strong>bold</strong> e <em>italico</em></p>'
        . '<ul><li>a</li><li>b</li></ul><a href="https://vivensi.app.br">link</a>';

    $this->actingAs($admin)->post('/admin/blog', [
        'title'   => 'Post ok',
        'content' => $html,
    ])->assertRedirect();

    $post = Post::latest('id')->first();
    expect($post->content)->toContain('<h2>');
    expect($post->content)->toContain('<strong>');
    expect($post->content)->toContain('<em>');
    expect($post->content)->toContain('<ul>');
    expect($post->content)->toContain('href="https://vivensi.app.br"');
});

it('update tambem sanitiza content', function () {
    $admin = bxsAdmin();

    $post = Post::create([
        'title'   => 'inicial',
        'slug'    => 'inicial',
        'content' => '<p>ok</p>',
    ]);

    $this->actingAs($admin)->put('/admin/blog/' . $post->id, [
        'title'   => 'atualizado',
        'content' => '<p>novo</p><iframe src="//evil.com"></iframe>',
    ])->assertRedirect();

    $post->refresh();
    expect($post->content)->not->toContain('<iframe');
    expect($post->content)->not->toContain('evil.com');
    expect($post->content)->toContain('novo');
});
