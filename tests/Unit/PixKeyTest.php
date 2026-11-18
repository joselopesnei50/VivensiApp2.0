<?php

use App\Support\PixKey;
use App\Support\PixPayload;

it('grava CPF e CNPJ só com números e confere os dígitos', function () {
    expect(PixKey::normalize('cpf_cnpj', '529.982.247-25'))->toBe('52998224725')
        ->and(PixKey::normalize('cpf_cnpj', '529.982.247-26'))->toBeNull()
        ->and(PixKey::normalize('cpf_cnpj', '111.111.111-11'))->toBeNull()
        ->and(PixKey::normalize('cpf_cnpj', '11.222.333/0001-81'))->toBe('11222333000181')
        ->and(PixKey::normalize('cpf_cnpj', '11.222.333/0001-82'))->toBeNull()
        ->and(PixKey::normalize('cpf_cnpj', '123'))->toBeNull();
});

it('aceita o CNPJ alfanumérico (exemplo oficial da Receita)', function () {
    expect(PixKey::normalize('cpf_cnpj', '12.abc.345/01de-35'))->toBe('12ABC34501DE35')
        ->and(PixKey::normalize('cpf_cnpj', '12.ABC.345/01DE-36'))->toBeNull()
        ->and(PixKey::display('12ABC34501DE35', 'cpf_cnpj'))->toBe('12.ABC.345/01DE-35');
});

it('grava o celular com +55 e recusa telefone fixo', function () {
    expect(PixKey::normalize('phone', '(16) 98839-2853'))->toBe('+5516988392853')
        ->and(PixKey::normalize('phone', '+55 16 98839-2853'))->toBe('+5516988392853')
        ->and(PixKey::normalize('phone', '(16) 3333-4444'))->toBeNull()
        ->and(PixKey::normalize('phone', '988392853'))->toBeNull()
        ->and(PixKey::display('+5516988392853', 'phone'))->toBe('+55 (16) 98839-2853');
});

it('grava e-mail e chave aleatória em minúsculas', function () {
    expect(PixKey::normalize('email', ' Financeiro@ONG.org.br '))->toBe('financeiro@ong.org.br')
        ->and(PixKey::normalize('email', 'financeiro@'))->toBeNull()
        ->and(PixKey::normalize('random', '123E4567-E89B-12D3-A456-426614174000'))->toBe('123e4567-e89b-12d3-a456-426614174000')
        ->and(PixKey::normalize('random', '123e4567e89b'))->toBeNull()
        ->and(PixKey::normalize(null, 'x@y.com'))->toBeNull();
});

it('reconhece o tipo das chaves antigas e corrige para o QR Code', function () {
    expect(PixKey::detect('pix@ong.org'))->toBe('email')
        ->and(PixKey::detect('11.222.333/0001-81'))->toBe('cpf_cnpj')
        ->and(PixKey::detect('529.982.247-25'))->toBe('cpf_cnpj')
        ->and(PixKey::detect('(16) 98839-2853'))->toBe('phone')
        ->and(PixKey::forPayload('11.222.333/0001-81'))->toBe('11222333000181')
        ->and(PixKey::forPayload('(16) 98839-2853'))->toBe('+5516988392853')
        ->and(PixKey::forPayload('Pix@ONG.org'))->toBe('pix@ong.org')
        ->and(PixKey::forPayload('qualquer coisa '))->toBe('qualquer coisa');
});

it('gera o BR Code igual ao exemplo do manual do Banco Central', function () {
    $code = PixPayload::build('123e4567-e12b-12d1-a456-426655440000', 'Fulano de Tal', 'BRASILIA', null);

    expect($code)->toBe('00020126580014br.gov.bcb.pix0136123e4567-e12b-12d1-a456-4266554400005204000053039865802BR5913Fulano de Tal6008BRASILIA62070503***63041D3D');
});
