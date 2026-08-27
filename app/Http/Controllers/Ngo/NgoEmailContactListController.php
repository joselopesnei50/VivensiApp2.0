<?php

namespace App\Http\Controllers\Ngo;

use App\Http\Controllers\Controller;
use App\Models\EmailContact;
use App\Models\EmailContactList;
use App\Services\EmailContactListImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * CRUD de listas de contatos reutilizaveis pra campanhas de e-mail no painel
 * NGO. Espelha /admin/email-campaigns/lists mas isolado por tenant do usuario
 * logado (sem depender de super_admin).
 */
class NgoEmailContactListController extends Controller
{
    public function index()
    {
        $lists = EmailContactList::where('tenant_id', auth()->user()->tenant_id)
            ->withCount(['contacts', 'activeContacts'])
            ->orderByDesc('updated_at')
            ->paginate(15);
        return view('ngo.email_campaigns.lists.index', compact('lists'));
    }

    public function create()
    {
        return view('ngo.email_campaigns.lists.create');
    }

    public function store(Request $request, EmailContactListImportService $importer)
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:150'],
            'description'      => ['nullable', 'string', 'max:1000'],
            'tags'             => ['nullable', 'string', 'max:500'],
            'opt_in_confirmed' => ['accepted'],
            'csv'              => ['nullable', 'file', 'max:10240', 'mimes:csv,txt'],
        ], [
            'opt_in_confirmed.accepted' => 'Você precisa confirmar o consentimento LGPD dos contatos antes de subir a lista.',
        ]);

        $list = EmailContactList::create([
            'tenant_id'           => auth()->user()->tenant_id,
            'name'                => $data['name'],
            'description'         => $data['description'] ?? null,
            'tags'                => $this->parseTags($data['tags'] ?? ''),
            'opt_in_confirmed'    => true,
            'opt_in_confirmed_at' => now(),
            'created_by'          => auth()->id(),
        ]);

        $importSummary = null;
        if ($request->hasFile('csv')) {
            $importSummary = $importer->importFromCsv($list, $request->file('csv'));
        }

        Log::info('NGO EmailContactList criada', [
            'list_id'   => $list->id,
            'tenant_id' => $list->tenant_id,
            'user_id'   => auth()->id(),
            'imported'  => $importSummary['imported'] ?? 0,
        ]);

        return redirect()->route('ngo.email_campaigns.lists.show', $list)
            ->with('success', $importSummary
                ? "Lista criada. Importados: {$importSummary['imported']} · Duplicados: {$importSummary['duplicates_in_list']} · Inválidos: {$importSummary['invalid_emails']}"
                : 'Lista criada. Adicione contatos manualmente ou faça upload de CSV.');
    }

    public function show(EmailContactList $list, Request $request)
    {
        $this->authorizeSameTenant($list);
        $q = trim((string) $request->query('q', ''));

        $contactsQuery = EmailContact::where('email_contact_list_id', $list->id);
        if ($q !== '') {
            $contactsQuery->where(function ($sub) use ($q) {
                $sub->where('email', 'like', "%{$q}%")
                    ->orWhere('name',  'like', "%{$q}%");
            });
        }
        $contacts = $contactsQuery->orderBy('email')->paginate(50)->appends(['q' => $q]);
        $stats = $list->stats();

        return view('ngo.email_campaigns.lists.show', compact('list', 'contacts', 'stats', 'q'));
    }

    public function importCsv(Request $request, EmailContactList $list, EmailContactListImportService $importer)
    {
        $this->authorizeSameTenant($list);
        $request->validate([
            'csv' => ['required', 'file', 'max:10240', 'mimes:csv,txt'],
        ]);

        $summary = $importer->importFromCsv($list, $request->file('csv'));

        return redirect()->route('ngo.email_campaigns.lists.show', $list)
            ->with('success', "Importados: {$summary['imported']} · Duplicados: {$summary['duplicates_in_list']} · Inválidos: {$summary['invalid_emails']}");
    }

    public function addContact(Request $request, EmailContactList $list)
    {
        $this->authorizeSameTenant($list);
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'name'  => ['nullable', 'string', 'max:200'],
        ]);

        $exists = EmailContact::where('email_contact_list_id', $list->id)
            ->where('email', mb_strtolower($data['email']))
            ->exists();
        if ($exists) {
            return back()->with('error', 'Este e-mail já está na lista.');
        }

        EmailContact::create([
            'email_contact_list_id' => $list->id,
            'tenant_id'             => $list->tenant_id,
            'email'                 => mb_strtolower($data['email']),
            'name'                  => $data['name'] ?? null,
            'status'                => EmailContact::STATUS_ACTIVE,
            'source'                => 'manual',
            'added_at'              => now(),
        ]);

        return back()->with('success', 'Contato adicionado.');
    }

    public function removeContact(EmailContactList $list, EmailContact $contact)
    {
        $this->authorizeSameTenant($list);
        if ($contact->email_contact_list_id !== $list->id) {
            abort(404);
        }
        $contact->delete();
        return back()->with('success', 'Contato removido.');
    }

    public function exportCsv(EmailContactList $list)
    {
        $this->authorizeSameTenant($list);

        $filename = 'lista_' . preg_replace('/[^a-z0-9]+/i', '_', mb_strtolower($list->name)) . '_' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($list) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'name', 'status', 'added_at']);
            EmailContact::where('email_contact_list_id', $list->id)
                ->orderBy('email')
                ->chunk(500, function ($chunk) use ($out) {
                    foreach ($chunk as $c) {
                        fputcsv($out, [$c->email, $c->name, $c->status, $c->added_at?->toIso8601String()]);
                    }
                });
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function destroy(EmailContactList $list)
    {
        $this->authorizeSameTenant($list);
        $list->delete(); // cascade nos contatos via FK
        return redirect()->route('ngo.email_campaigns.lists.index')
            ->with('success', 'Lista removida.');
    }

    private function authorizeSameTenant(EmailContactList $list): void
    {
        if ((int) $list->tenant_id !== (int) auth()->user()->tenant_id) {
            abort(404);
        }
    }

    private function parseTags(string $raw): array
    {
        if (trim($raw) === '') return [];
        $tags = array_filter(array_map(
            fn ($t) => trim(mb_strtolower($t)),
            explode(',', $raw)
        ));
        return array_values(array_unique($tags));
    }
}
