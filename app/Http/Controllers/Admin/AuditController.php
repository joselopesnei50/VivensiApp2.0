<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DeepSeekService;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function __construct(private DeepSeekService $deepSeek) {}

    public function index()
    {
        return view('admin.audit');
    }

    public function runCheck(Request $request)
    {
        $request->validate([
            'prompt'      => 'required|string|max:3000',
            'context'     => 'nullable|string|max:1000',
        ]);

        $system = "Você é um auditor técnico sênior especializado em Laravel, PHP, AWS e SaaS B2B para o terceiro setor brasileiro.\n"
            . "Você está auditando o Vivensi (vivensi.app.br) — plataforma SaaS para ONGs, institutos e associações.\n"
            . "Stack: Laravel (PHP 8.x), Livewire 3, FilamentPHP 3, Tailwind CSS, MySQL (AWS RDS), Redis (ElastiCache), S3 + CloudFront, Laravel Queues + Supervisor, Evolution API (WhatsApp), PIX/OpenPix.\n";

        if ($request->filled('context')) {
            $system .= "Contexto adicional: " . $request->context . "\n";
        }

        $system .= "\nResponda de forma estruturada e objetiva em português. Classifique cada problema como Alta/Média/Baixa severidade. Sugira correções concretas. Seja direto — o objetivo é identificar o que pode falhar antes das vendas de assinatura.";

        $response = $this->deepSeek->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user',   'content' => $request->prompt],
        ]);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']], 500);
        }

        $text = data_get($response, 'choices.0.message.content', 'Sem resposta.');

        return response()->json(['text' => $text]);
    }
}
