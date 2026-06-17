<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\KanbanBoard;
use App\Models\KanbanCard;
use App\Models\KanbanColumn;
use App\Models\WhatsappChat;
use App\Services\KanbanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * KanbanController — Fase 3 (item 2.2 do roadmap).
 *
 * Endpoints HTTP do Kanban geral do painel Manager. Provisiona board default
 * sob demanda; CRUD básico de colunas e cards + endpoint de move usado pelo
 * SortableJS no frontend.
 */
class KanbanController extends Controller
{
    public function __construct(private KanbanService $service)
    {
    }

    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        abort_if($tenant === null, 403, 'Usuário sem tenant ativo.');

        $board   = $this->service->ensureDefaultBoard($tenant, $request->user());
        $columns = $this->service->loadBoardForRender($board);

        return view('manager.kanban.index', compact('board', 'columns'));
    }

    public function storeColumn(Request $request, KanbanBoard $board): JsonResponse
    {
        $this->ensureTenant($request, $board);

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:9'],
        ]);

        $tail = (int) KanbanColumn::where('board_id', $board->id)->max('position');
        $column = KanbanColumn::create([
            'tenant_id' => $board->tenant_id,
            'board_id'  => $board->id,
            'name'      => $data['name'],
            'color'     => $data['color'] ?? null,
            'position'  => $tail + KanbanService::POSITION_GAP,
        ]);

        return response()->json(['column' => $column]);
    }

    public function destroyColumn(Request $request, KanbanColumn $column): JsonResponse
    {
        $this->ensureTenant($request, $column);
        $column->delete();
        return response()->json(['ok' => true]);
    }

    public function storeCard(Request $request, KanbanColumn $column): JsonResponse
    {
        $this->ensureTenant($request, $column);

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $card = $this->service->createCard($column, $data, $request->user());
        return response()->json(['card' => $card]);
    }

    /**
     * Atualiza coluna e posição de um card a partir do drag-and-drop.
     * Body: { column_id, ordered_card_ids: [...] } — front envia a lista
     * final da coluna alvo, incluindo o card movido na posição certa.
     */
    public function moveCard(Request $request, KanbanCard $card): JsonResponse
    {
        $this->ensureTenant($request, $card);

        $data = $request->validate([
            'column_id'        => ['required', 'integer'],
            'ordered_card_ids' => ['required', 'array', 'min:1'],
            'ordered_card_ids.*' => ['integer'],
        ]);

        /** @var KanbanColumn $target */
        $target = KanbanColumn::where('id', $data['column_id'])
            ->where('tenant_id', $card->tenant_id)
            ->firstOrFail();

        $card = $this->service->moveCard($card, $target, $data['ordered_card_ids']);

        return response()->json(['card' => $card]);
    }

    public function updateCard(Request $request, KanbanCard $card): JsonResponse
    {
        $this->ensureTenant($request, $card);

        $data = $request->validate([
            'title'       => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'assigned_to' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'due_date'    => ['sometimes', 'nullable', 'date'],
        ]);

        $card->update($data);
        return response()->json(['card' => $card->fresh()]);
    }

    public function archiveCard(Request $request, KanbanCard $card): JsonResponse
    {
        $this->ensureTenant($request, $card);
        $card = $this->service->archiveCard($card);
        return response()->json(['card' => $card]);
    }

    /**
     * Lista de colunas do board default do tenant. Consumido pelo accordion
     * do OmniChannel ("Enviar p/ Kanban Geral") pra popular o select sem
     * precisar carregar o board inteiro.
     */
    public function columnsList(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        abort_if($tenant === null, 403);

        $board   = $this->service->ensureDefaultBoard($tenant, $request->user());
        $columns = KanbanColumn::where('board_id', $board->id)
            ->orderBy('position')
            ->get(['id', 'name', 'color']);

        return response()->json([
            'board_id' => $board->id,
            'columns'  => $columns,
        ]);
    }

    /**
     * Cria card a partir de uma conversa do WhatsApp — critério de aceite
     * do item 2.2 do roadmap: o card mantém link de volta para a conversa
     * via whatsapp_chat_id.
     */
    public function storeCardFromWhatsapp(Request $request, WhatsappChat $chat): JsonResponse
    {
        $this->ensureTenant($request, $chat);

        $data = $request->validate([
            'column_id'   => ['required', 'integer'],
            'title'       => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        /** @var KanbanColumn $column */
        $column = KanbanColumn::where('id', $data['column_id'])
            ->where('tenant_id', $chat->tenant_id)
            ->firstOrFail();

        $card = $this->service->createCard($column, [
            'whatsapp_chat_id' => $chat->id,
            'title'            => $data['title'] ?: ($chat->contact_name ?: ($chat->contact_phone ?: 'Conversa WhatsApp')),
            'description'      => $data['description'] ?? null,
        ], $request->user());

        return response()->json(['card' => $card]);
    }

    /**
     * Guard: garante que o recurso pertence ao tenant do usuário autenticado.
     * Vai pra cima de qualquer mutação — defesa em camadas contra IDOR.
     */
    private function ensureTenant(Request $request, $resource): void
    {
        $tenantId = $request->user()->tenant_id;
        abort_if(
            !$resource || (int) $resource->tenant_id !== (int) $tenantId,
            404
        );
    }
}
