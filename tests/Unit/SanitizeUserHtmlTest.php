<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * sanitize_user_html() com HTMLPurifier (mews/purifier) — prova que os
 * vetores clássicos de XSS armazenado são removidos e que HTML seguro
 * de editor rico é preservado.
 */
class SanitizeUserHtmlTest extends TestCase
{
    /** @test */
    public function bloqueia_script_tag(): void
    {
        $input = '<p>texto</p><script>alert(1)</script>';

        $output = sanitize_user_html($input);

        $this->assertStringNotContainsString('<script', $output);
        $this->assertStringNotContainsString('alert(1)', $output);
        $this->assertStringContainsString('<p>texto</p>', $output);
    }

    /** @test */
    public function bloqueia_javascript_em_href(): void
    {
        $output = sanitize_user_html('<a href="javascript:alert(1)">clique</a>');

        $this->assertStringNotContainsString('javascript:', $output);
    }

    /** @test */
    public function bloqueia_event_handler_inline(): void
    {
        $output = sanitize_user_html('<img src="x" onerror="alert(1)">');

        $this->assertStringNotContainsString('onerror', $output);
    }

    /** @test */
    public function bloqueia_iframe_object_e_form(): void
    {
        $input = '<iframe src="https://evil.example"></iframe>'
               . '<object data="x"></object>'
               . '<form action="/x"><input name="a"></form>';

        $output = sanitize_user_html($input);

        $this->assertStringNotContainsString('<iframe', $output);
        $this->assertStringNotContainsString('<object', $output);
        $this->assertStringNotContainsString('<form', $output);
        $this->assertStringNotContainsString('<input', $output);
    }

    /** @test */
    public function bloqueia_data_uri_em_src(): void
    {
        $output = sanitize_user_html('<img src="data:text/html;base64,PHNjcmlwdD4=">');

        $this->assertStringNotContainsString('data:', $output);
    }

    /** @test */
    public function remove_style_inline(): void
    {
        $output = sanitize_user_html('<p style="background:url(javascript:alert(1))">oi</p>');

        $this->assertStringNotContainsString('style=', $output);
        $this->assertStringContainsString('oi', $output);
    }

    /** @test */
    public function preserva_html_seguro_de_editor_rico(): void
    {
        $input = '<h2>Título</h2><p>Parágrafo <strong>negrito</strong> e '
               . '<a href="https://exemplo.com" title="ex">link</a>.</p>'
               . '<ul><li>item</li></ul>'
               . '<img src="https://exemplo.com/foto.jpg" alt="foto">';

        $output = sanitize_user_html($input);

        $this->assertStringContainsString('<h2>Título</h2>', $output);
        $this->assertStringContainsString('<strong>negrito</strong>', $output);
        $this->assertStringContainsString('https://exemplo.com', $output);
        $this->assertStringContainsString('<li>item</li>', $output);
        $this->assertStringContainsString('foto.jpg', $output);
    }

    /** @test */
    public function entrada_nula_ou_vazia_devolve_string_vazia(): void
    {
        $this->assertSame('', sanitize_user_html(null));
        $this->assertSame('', sanitize_user_html(''));
    }
}
