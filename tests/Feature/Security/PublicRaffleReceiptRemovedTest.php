<?php

/**
 * Regressao — auditoria 2026-08-29 P3.b.2 (baixa, resolvido em 2026-08-31).
 *
 * A rota POST /rifa/ticket/{ticket}/comprovante e o metodo
 * PublicRaffleController::uploadReceipt foram REMOVIDOS por ser dead code
 * (nenhuma view/email chamava). Este teste garante que se alguem reintroduzir
 * a rota sem revisar, o build quebra — obrigando decisao consciente sobre
 * como blindar (token HMAC bidx e o padrao correto).
 */

it('rota antiga POST /rifa/ticket/{id}/comprovante retorna 404 (removida)', function () {
    $this->post('/rifa/ticket/1/comprovante', [])->assertStatus(404);
});

it('rota GET tambem retorna 404 (era so POST, mas garantia dupla)', function () {
    $this->get('/rifa/ticket/1/comprovante')->assertStatus(404);
});

it('metodo uploadReceipt nao existe mais no controller', function () {
    expect(method_exists(\App\Http\Controllers\PublicRaffleController::class, 'uploadReceipt'))
        ->toBeFalse();
});
