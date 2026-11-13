<?php

use Illuminate\Session\TokenMismatchException;

/* Páginas de erro com a marca da Vivensi, em português. */

it('manutenção, muitas tentativas e sessão expirada têm página própria em português', function () {
    expect(view('errors.503')->render())->toContain('Estamos atualizando a Vivensi')->toContain('http-equiv="refresh"');
    expect(view('errors.429')->render())->toContain('Muitas tentativas');
    expect(view('errors.419')->render())->toContain('Sua sessão expirou')->not->toContain('Page Expired');
});

it('sessão expirada ao enviar o login volta para o login com aviso, sem a senha', function () {
    \Illuminate\Support\Facades\Route::post('/_teste-419', fn () => throw new TokenMismatchException())->middleware('web');

    $this->from('/login')->post('/_teste-419', ['email' => 'a@b.com', 'password' => 'segredo123456'])
        ->assertRedirect('/login')
        ->assertSessionHas('error', fn ($m) => str_contains($m, 'sessão expirou'))
        ->assertSessionHasInput('email', 'a@b.com')
        ->assertSessionMissing('_old_input.password');
});

it('em manutenção o site mostra a página da Vivensi', function () {
    $this->artisan('down', ['--render' => 'errors::503']);
    try {
        $this->get('/')->assertStatus(503)->assertSee('Estamos atualizando a Vivensi');
    } finally {
        $this->artisan('up');
    }
});
