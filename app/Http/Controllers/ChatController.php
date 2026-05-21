<?php

namespace App\Http\Controllers;

use App\Services\DeepSeekService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected $deepSeek;

    public function __construct(DeepSeekService $deepSeek)
    {
        $this->deepSeek = $deepSeek;
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'history' => 'array' // Optional: pass previous context
        ]);

        $userMessage = $request->input('message');
        
        $user = auth()->user();
        $role = $user->role ?? 'common';
        $userName = $user->name ?? 'Amigo';

        // Base Identity
        $baseSystemPrompt = "Você é o Bruce AI, o assistente inteligente e mascote do sistema Vivensi (um Golden Retriever financeiro 🐶). Sua missão é ajudar o usuário com finanças e dúvidas do sistema. Personalidade: Amigável, leal e prestativo. Usa emojis moderadamente (🐾, 💡, ✅). Explica termos financeiros complexos de forma simples ('traduzindo o economês'). Se não souber algo, diga que vai 'farejar a resposta' e sugira contatar o suporte humano. Responda sempre em Markdown limpo (use negrito para destaque, listas para passos). Evite textos longos demais.";

        // Role-Specific Context
        $contextPrompt = "";
        if ($user->isNgo() || ($user->tenant && $user->tenant->type === 'ngo')) {
            $contextPrompt = "O usuário é uma ONG/OSC. Foque em: Finanças do Terceiro Setor, Prestação de Contas, Transparência, Doadores e Editais. Use termos como 'Entidade', 'Recursos', 'Doador'.";
        } elseif ($user->isManager()) {
            $contextPrompt = "O usuário é um Gestor de Projetos. Foque em: Cronogramas, Alocação de Recursos, Prazos, Equipe e Orçamento de Projetos. Use termos corporativos leves.";
        } else {
            // Common / Personal
            $contextPrompt = "O usuário é uma Pessoa Física (Cliente Comum). Foque em: Finanças Pessoais, Controle de Gastos Domésticos, Economia, Planejamento de Orçamento Familiar e Conciliação Bancária Pessoal. Não use termos complexos de contabilidade empresarial. Trate como um amigo ajudando a organizar as contas da casa.";
        }

        $fullPrompt = "{$baseSystemPrompt} \nCONTEXTO DO USUÁRIO: {$contextPrompt}. O nome do usuário é {$userName}.";

        // Contexto do sistema
        $messages = [
            ['role' => 'system', 'content' => $fullPrompt]
        ];

        // Adicionar histórico se houver (para manter conversação)
        if ($request->has('history')) {
            $messages = array_merge($messages, $request->input('history'));
        }

        // Adicionar mensagem atual
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $response = $this->deepSeek->chat($messages);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']], 500);
        }

        // Extrair resposta da IA
        $aiReply = $response['choices'][0]['message']['content'] ?? 'Desculpe, não consegui processar sua resposta.';

        return response()->json(['reply' => $aiReply]);
    }
}
