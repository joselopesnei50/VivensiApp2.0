<?php

use App\Services\LeadSearchService;

/**
 * Auditoria 2026-08-29 P2 (media) — bloqueio de URLs com scheme != http(s)
 * antes de gravar em prospects.website. Vetor: XSS via javascript:/data:
 * quando admin do painel /prospecting clica no link.
 */

test('aceita http e https', function () {
    expect(LeadSearchService::sanitizeExternalUrl('http://example.com'))->toBe('http://example.com');
    expect(LeadSearchService::sanitizeExternalUrl('https://example.com/path?q=1'))->toBe('https://example.com/path?q=1');
    expect(LeadSearchService::sanitizeExternalUrl('HTTPS://EXAMPLE.COM'))->toBe('HTTPS://EXAMPLE.COM');
});

test('rejeita javascript:', function () {
    expect(LeadSearchService::sanitizeExternalUrl('javascript:alert(document.cookie)'))->toBeNull();
    expect(LeadSearchService::sanitizeExternalUrl('JAVASCRIPT:alert(1)'))->toBeNull();
    expect(LeadSearchService::sanitizeExternalUrl(' javascript:void(0) '))->toBeNull();
});

test('rejeita data:', function () {
    expect(LeadSearchService::sanitizeExternalUrl('data:text/html,<script>alert(1)</script>'))->toBeNull();
});

test('rejeita file:', function () {
    expect(LeadSearchService::sanitizeExternalUrl('file:///etc/passwd'))->toBeNull();
});

test('rejeita URL relativa e protocol-relative', function () {
    expect(LeadSearchService::sanitizeExternalUrl('//evil.com'))->toBeNull();
    expect(LeadSearchService::sanitizeExternalUrl('/local/path'))->toBeNull();
    expect(LeadSearchService::sanitizeExternalUrl('example.com'))->toBeNull();
});

test('rejeita null, vazio e whitespace', function () {
    expect(LeadSearchService::sanitizeExternalUrl(null))->toBeNull();
    expect(LeadSearchService::sanitizeExternalUrl(''))->toBeNull();
    expect(LeadSearchService::sanitizeExternalUrl('   '))->toBeNull();
});

test('trim aplicado antes da validacao', function () {
    expect(LeadSearchService::sanitizeExternalUrl('  https://example.com  '))->toBe('https://example.com');
});

test('rejeita URL malformada mesmo com scheme http', function () {
    // filter_var URL detecta URIs quebradas
    expect(LeadSearchService::sanitizeExternalUrl('http://'))->toBeNull();
    expect(LeadSearchService::sanitizeExternalUrl('https:// evil.com'))->toBeNull();
});
