<?php

namespace App\Http\Requests\Manager;

use App\Models\TenantOperationalProfile;
use App\Support\PiiSniffer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação da edição do Perfil Operacional (Fase 1 — Etapa B).
 *
 * Pré-requisitos exigidos pelo lgpd-eleitoral-guardian:
 *  - Whitelist de categoria (sem fallback silencioso para 'outro').
 *  - Limite de tamanho na instrução (desencoraja dump de base).
 *  - Regex anti-PII no campo instrucao (CPF, telefone, e-mail) — o texto
 *    vai parar inteiro no system prompt do Bruce; PII embutida vazaria
 *    para o provedor de IA e para qualquer logs futuros do prompt.
 */
class UpdatePerfilOperacionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('access-manager');
    }

    public function rules(): array
    {
        return [
            'categoria' => [
                'required',
                'string',
                Rule::in(array_keys(TenantOperationalProfile::CATEGORIAS)),
            ],
            'instrucao' => [
                'nullable',
                'string',
                'max:2000',
                function ($attribute, $value, $fail): void {
                    if (!is_string($value) || $value === '') {
                        return;
                    }
                    switch (PiiSniffer::detect($value)) {
                        case 'cpf':
                            $fail('A instrução não pode conter CPF. Use linguagem genérica sobre o público, não pessoas específicas.');
                            return;
                        case 'phone':
                            $fail('A instrução não pode conter número de telefone. Configure o canal WhatsApp em Configurações > WhatsApp, não aqui.');
                            return;
                        case 'email':
                            $fail('A instrução não pode conter e-mails. Use apenas instruções genéricas para o assistente.');
                            return;
                    }
                },
            ],
            'vocabulario'   => ['nullable', 'array'],
            // Whitelist de caracteres: letras (Unicode), dígitos, espaço, hífen, ponto
            // e apóstrofo. Bloqueia <, >, aspas, {{}}, quebras de linha — evita XSS e
            // prompt-injection quando o rótulo for emitido na UI ou no prompt do Bruce.
            'vocabulario.*' => ['nullable', 'string', 'max:60', 'regex:/^[\p{L}\p{N}\s\-\.\']+$/u'],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria.required' => 'Escolha uma categoria para o perfil operacional.',
            'categoria.in'       => 'Categoria inválida.',
            'instrucao.max'      => 'A instrução não pode passar de 2.000 caracteres.',
        ];
    }
}
