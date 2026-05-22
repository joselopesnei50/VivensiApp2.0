<?php

namespace App\Http\Controllers;

use App\Services\WebhookService;

class ApiDocsController extends Controller
{
    public function index()
    {
        $baseUrl    = config('app.url') . '/api/v1';
        $webhookEvents = WebhookService::EVENTS;

        $endpoints = [
            [
                'group'  => 'Autenticação',
                'color'  => '#818cf8',
                'routes' => [
                    ['GET',  '/me', 'Retorna dados do usuário autenticado e tenant.', [], [
                        'id' => 5, 'name' => 'João Silva', 'email' => 'joao@empresa.com',
                        'role' => 'manager', 'tenant' => ['id' => 2, 'name' => 'Empresa SA'],
                    ]],
                ],
            ],
            [
                'group'  => 'Transações',
                'color'  => '#34d399',
                'routes' => [
                    ['GET',  '/transactions', 'Lista transações do tenant. Filtros: type, status, from (Y-m-d), to (Y-m-d), per_page (max 100).', [], null],
                    ['POST', '/transactions', 'Cria uma transação.', [
                        'description' => 'Venda de produto',
                        'amount'      => 250.00,
                        'date'        => '2026-05-22',
                        'type'        => 'income',   // income | expense
                        'status'      => 'paid',      // paid | pending | canceled
                        'category_id' => null,
                        'project_id'  => null,
                    ], null],
                    ['GET',    '/transactions/{id}', 'Retorna uma transação pelo ID.', [], null],
                    ['PATCH',  '/transactions/{id}', 'Atualiza campos de uma transação.', [
                        'description' => 'Descrição atualizada',
                        'amount'      => 300.00,
                        'status'      => 'paid',
                    ], null],
                    ['DELETE', '/transactions/{id}', 'Remove uma transação (soft-delete).', [], null],
                ],
            ],
            [
                'group'  => 'Projetos',
                'color'  => '#f59e0b',
                'routes' => [
                    ['GET', '/projects',           'Lista projetos. Filtro: status (active|paused|completed|canceled).', [], null],
                    ['GET', '/projects/{id}',      'Retorna um projeto pelo ID.', [], null],
                    ['GET', '/projects/{id}/tasks','Lista tarefas de um projeto. Filtro: status.', [], null],
                ],
            ],
            [
                'group'  => 'NGO — Terceiro Setor',
                'color'  => '#10b981',
                'routes' => [
                    ['GET',  '/ngo/summary',     'Resumo: total de doadores, editais e valor captado. Exclusivo role ngo.', [], null],
                    ['GET',  '/ngo/donors',      'Lista doadores. Filtros: type (individual|company|government), search.', [], null],
                    ['POST', '/ngo/donors',      'Cadastra novo doador.', ['name'=>'Maria Silva','email'=>'maria@ong.org','type'=>'individual'], null],
                    ['GET',  '/ngo/donors/{id}', 'Retorna um doador pelo ID.', [], null],
                    ['GET',  '/ngo/grants',      'Lista editais. Filtro: status.', [], null],
                    ['GET',  '/ngo/grants/{id}', 'Retorna um edital pelo ID.', [], null],
                ],
            ],
            [
                'group'  => 'Tarefas',
                'color'  => '#6366f1',
                'routes' => [
                    ['GET',    '/tasks',      'Lista tarefas. Filtros: status, priority, project_id.', [], null],
                    ['POST',   '/tasks',      'Cria uma tarefa.', [
                        'title'      => 'Revisar relatório',
                        'status'     => 'todo',      // todo|doing|done|pending|in_progress|completed|blocked
                        'priority'   => 'medium',    // low|medium|high|critical
                        'project_id' => null,
                        'due_date'   => '2026-06-01',
                    ], null],
                    ['GET',    '/tasks/{id}', 'Retorna uma tarefa pelo ID.', [], null],
                    ['PATCH',  '/tasks/{id}', 'Atualiza campos de uma tarefa. Dispara webhook task.completed ao concluir.', [
                        'status'   => 'done',
                        'priority' => 'high',
                    ], null],
                    ['DELETE', '/tasks/{id}', 'Remove uma tarefa.', [], null],
                ],
            ],
        ];

        return view('docs.api', compact('baseUrl', 'endpoints', 'webhookEvents'));
    }
}
