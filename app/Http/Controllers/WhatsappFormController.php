<?php

namespace App\Http\Controllers;

use App\Models\WhatsappForm;
use App\Models\WhatsappFormQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * CRUD de formulários conversacionais WhatsApp (Fase 4 — item 2.5).
 *
 * O engine FSM já existia (WhatsappFormEngine + integração no webhook), e
 * o chat já disparava formulários ativos. Faltava a interface para o gestor
 * criar/editar os formulários — antes só existia via DemoFormSeeder ou
 * insert manual no banco.
 *
 * field_keys "mágicos" (reservados): phone, email, name, city, tags.
 * Quando uma pergunta usa um desses, LeadCaptureFromForm mapeia
 * automaticamente o valor para a coluna correspondente do Lead.
 * Qualquer outro field_key vai para meta JSON.
 */
class WhatsappFormController extends Controller
{
    public const RESERVED_FIELD_KEYS = ['phone', 'email', 'name', 'city', 'tags'];

    public function index(Request $request)
    {
        Gate::authorize('access-whatsapp');

        $tenantId = $request->user()->tenant_id;
        $forms = WhatsappForm::where('tenant_id', $tenantId)
            ->withCount('questions')
            ->orderByDesc('id')
            ->paginate(20);

        return view('whatsapp.forms.index', compact('forms'));
    }

    public function create()
    {
        Gate::authorize('access-whatsapp');
        return view('whatsapp.forms.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('access-whatsapp');

        $data = $request->validate([
            'name'        => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'nullable|boolean',
        ]);

        $form = WhatsappForm::create([
            'tenant_id'   => $request->user()->tenant_id,
            'created_by'  => $request->user()->id,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active'   => (bool) ($data['is_active'] ?? true),
        ]);

        return redirect()->route('whatsapp.forms.edit', $form->id)
            ->with('success', 'Formulário criado. Agora adicione as perguntas abaixo.');
    }

    public function edit(Request $request, int $id)
    {
        Gate::authorize('access-whatsapp');

        $tenantId = $request->user()->tenant_id;
        $form = WhatsappForm::where('tenant_id', $tenantId)->findOrFail($id);
        $questions = $form->questions()->get();

        return view('whatsapp.forms.edit', compact('form', 'questions'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('access-whatsapp');

        $tenantId = $request->user()->tenant_id;
        $form = WhatsappForm::where('tenant_id', $tenantId)->findOrFail($id);

        $data = $request->validate([
            'name'        => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'nullable|boolean',
        ]);

        $form->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active'   => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', 'Formulário atualizado.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('access-whatsapp');

        $tenantId = $request->user()->tenant_id;
        $form = WhatsappForm::where('tenant_id', $tenantId)->findOrFail($id);
        $form->delete();

        return redirect()->route('whatsapp.forms.index')->with('success', 'Formulário removido.');
    }

    public function duplicate(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('access-whatsapp');

        $tenantId = $request->user()->tenant_id;
        $original = WhatsappForm::where('tenant_id', $tenantId)
            ->with('questions')
            ->findOrFail($id);

        $copy = DB::transaction(function () use ($original, $request) {
            $clone = $original->replicate(['created_at', 'updated_at']);
            $clone->name = $original->name . ' (cópia)';
            $clone->is_active = false;
            $clone->created_by = $request->user()->id;
            $clone->save();
            foreach ($original->questions as $q) {
                $qClone = $q->replicate(['created_at', 'updated_at']);
                $qClone->form_id = $clone->id;
                $qClone->save();
            }
            return $clone;
        });

        return redirect()->route('whatsapp.forms.edit', $copy->id)
            ->with('success', 'Formulário duplicado. Está inativo até você revisar.');
    }

    // ── Perguntas (AJAX, retornam JSON) ─────────────────────────────────────

    public function addQuestion(Request $request, int $formId): JsonResponse
    {
        Gate::authorize('access-whatsapp');

        $form = $this->findFormForCurrentTenant($request, $formId);

        $data = $this->validateQuestionPayload($request);

        $position = ($form->questions()->max('position') ?? -1) + 1;

        $q = WhatsappFormQuestion::create([
            'form_id'          => $form->id,
            'position'         => $position,
            'field_key'        => $data['field_key'],
            'text'             => $data['text'],
            'type'             => $data['type'],
            'options'          => $data['options'] ?? null,
            'required'         => (bool) ($data['required'] ?? true),
            'validation_regex' => $data['validation_regex'] ?? null,
            'min_value'        => $data['min_value'] ?? null,
            'max_value'        => $data['max_value'] ?? null,
        ]);

        return response()->json(['success' => true, 'question' => $q]);
    }

    public function updateQuestion(Request $request, int $formId, int $questionId): JsonResponse
    {
        Gate::authorize('access-whatsapp');

        $form = $this->findFormForCurrentTenant($request, $formId);
        $q = WhatsappFormQuestion::where('form_id', $form->id)->findOrFail($questionId);

        $data = $this->validateQuestionPayload($request);

        $q->update([
            'field_key'        => $data['field_key'],
            'text'             => $data['text'],
            'type'             => $data['type'],
            'options'          => $data['options'] ?? null,
            'required'         => (bool) ($data['required'] ?? true),
            'validation_regex' => $data['validation_regex'] ?? null,
            'min_value'        => $data['min_value'] ?? null,
            'max_value'        => $data['max_value'] ?? null,
        ]);

        return response()->json(['success' => true, 'question' => $q->fresh()]);
    }

    public function deleteQuestion(Request $request, int $formId, int $questionId): JsonResponse
    {
        Gate::authorize('access-whatsapp');

        $form = $this->findFormForCurrentTenant($request, $formId);
        $q = WhatsappFormQuestion::where('form_id', $form->id)->findOrFail($questionId);
        $q->delete();

        return response()->json(['success' => true]);
    }

    public function reorderQuestions(Request $request, int $formId): JsonResponse
    {
        Gate::authorize('access-whatsapp');

        $form = $this->findFormForCurrentTenant($request, $formId);
        $ordered = $request->validate([
            'ordered_ids'   => 'required|array|min:1',
            'ordered_ids.*' => 'integer',
        ])['ordered_ids'];

        // Garante que todos os IDs pertencem ao form (anti-tampering).
        $belong = WhatsappFormQuestion::where('form_id', $form->id)
            ->whereIn('id', $ordered)
            ->pluck('id')
            ->all();
        if (count($belong) !== count($ordered)) {
            return response()->json(['success' => false, 'error' => 'IDs inválidos.'], 422);
        }

        DB::transaction(function () use ($form, $ordered) {
            foreach ($ordered as $i => $qId) {
                WhatsappFormQuestion::where('form_id', $form->id)
                    ->where('id', $qId)
                    ->update(['position' => $i]);
            }
        });

        return response()->json(['success' => true]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function findFormForCurrentTenant(Request $request, int $formId): WhatsappForm
    {
        return WhatsappForm::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($formId);
    }

    /**
     * @return array<string,mixed>
     */
    private function validateQuestionPayload(Request $request): array
    {
        return $request->validate([
            'field_key'        => 'required|string|max:60|regex:/^[a-z0-9_]+$/i',
            'text'             => 'required|string|max:1000',
            'type'             => ['required', Rule::in(WhatsappFormQuestion::TYPES)],
            'options'          => 'nullable|array',
            'options.*.id'     => 'nullable|string|max:60',
            'options.*.label'  => 'nullable|string|max:120',
            'required'         => 'nullable|boolean',
            'validation_regex' => 'nullable|string|max:200',
            'min_value'        => 'nullable|integer',
            'max_value'        => 'nullable|integer',
        ]);
    }
}
