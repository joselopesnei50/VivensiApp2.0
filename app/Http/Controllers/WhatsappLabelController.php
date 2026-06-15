<?php

namespace App\Http\Controllers;

use App\Models\WhatsappLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class WhatsappLabelController extends Controller
{
    /**
     * Tela de gerenciamento das etiquetas do tenant.
     */
    public function index()
    {
        Gate::authorize('access-whatsapp');

        $tenantId = auth()->user()->tenant_id;
        $labels   = WhatsappLabel::where('tenant_id', $tenantId)
            ->withCount('chats')
            ->orderBy('name')
            ->get();

        return view('whatsapp.labels', compact('labels'));
    }

    /**
     * Endpoint JSON usado pelo seletor de etiquetas do chat.
     */
    public function listJson()
    {
        Gate::authorize('access-whatsapp');

        $tenantId = auth()->user()->tenant_id;
        $labels   = WhatsappLabel::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'color', 'background']);

        return response()->json($labels);
    }

    /**
     * Cria etiqueta nova para o tenant atual.
     */
    public function store(Request $request)
    {
        Gate::authorize('access-whatsapp');

        $tenantId = auth()->user()->tenant_id;

        $data = $request->validate([
            'name'       => 'required|string|max:30',
            'color'      => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'background' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        $slug = $this->uniqueSlug($tenantId, $data['name']);

        $label = WhatsappLabel::create([
            'tenant_id'  => $tenantId,
            'name'       => $data['name'],
            'slug'       => $slug,
            'color'      => $data['color'],
            'background' => $data['background'],
            'created_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'label' => $label]);
    }

    /**
     * Atualiza nome ou cores de uma etiqueta. Slug é regenerado se nome mudar.
     * Pivot whatsapp_chat_label mantém a referência via ID, então rename é safe.
     */
    public function update(Request $request, WhatsappLabel $label)
    {
        Gate::authorize('access-whatsapp');

        $tenantId = auth()->user()->tenant_id;
        abort_if($label->tenant_id !== $tenantId, 403);

        $data = $request->validate([
            'name'       => 'required|string|max:30',
            'color'      => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'background' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        // Regenera slug somente se nome mudou, e garante unicidade dentro do tenant
        $slug = $label->name === $data['name']
            ? $label->slug
            : $this->uniqueSlug($tenantId, $data['name'], $label->id);

        $label->update([
            'name'       => $data['name'],
            'slug'       => $slug,
            'color'      => $data['color'],
            'background' => $data['background'],
        ]);

        return response()->json(['success' => true, 'label' => $label]);
    }

    /**
     * Remove etiqueta. Cascade do pivot remove automaticamente os vínculos
     * com chats (constraint cascadeOnDelete na migration de whatsapp_chat_label).
     */
    public function destroy(WhatsappLabel $label)
    {
        Gate::authorize('access-whatsapp');

        $tenantId = auth()->user()->tenant_id;
        abort_if($label->tenant_id !== $tenantId, 403);

        $label->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Gera slug único dentro do tenant. Se 'venda' já existe, tenta
     * 'venda-2', 'venda-3', etc. Se $excludeId for passado, ignora essa
     * etiqueta na checagem (caso de rename).
     */
    private function uniqueSlug(int $tenantId, string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name) ?: 'etiqueta';
        $slug = $base;
        $i    = 2;

        while (true) {
            $query = WhatsappLabel::where('tenant_id', $tenantId)->where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            if (!$query->exists()) {
                return $slug;
            }
            $slug = "{$base}-{$i}";
            $i++;
        }
    }
}
