<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransparencyPortal;
use App\Models\BoardMember;
use App\Models\TransparencyDocument;
use App\Models\PublicPartnership;
use App\Models\Transaction;
use App\Models\Beneficiary;
use App\Models\Employee;
use App\Models\Asset;
use Illuminate\Support\Str;

class TransparencyController extends Controller
{
    /**
     * NGO Dashboard for Transparency Configuration
     */
    public function index()
    {
        $tenant_id = auth()->user()->tenant_id;
        $portal = TransparencyPortal::firstOrCreate(['tenant_id' => $tenant_id], [
            'slug' => Str::slug(auth()->user()->name ?? 'ong-' . $tenant_id),
            'title' => 'Portal da Transparência',
            'is_published' => false
        ]);

        $board = BoardMember::where('tenant_id', $tenant_id)->get();
        $docs = TransparencyDocument::where('tenant_id', $tenant_id)->get();
        $partnerships = PublicPartnership::where('tenant_id', $tenant_id)->get();

        return view('ngo.transparency.index', compact('portal', 'board', 'docs', 'partnerships'));
    }

    public function updatePortal(Request $request)
    {
        $tenant_id = auth()->user()->tenant_id;
        $portal = TransparencyPortal::where('tenant_id', $tenant_id)->firstOrFail();
        
        $portal->update($request->only(['title', 'cnpj', 'mission', 'vision', 'values', 'sic_email', 'sic_phone', 'is_published']));
        
        return back()->with('success', 'Configurações do portal atualizadas!');
    }

    public function addBoardMember(Request $request)
    {
        $request->validate(['name' => 'required', 'position' => 'required']);
        $tenant_id = auth()->user()->tenant_id;
        
        BoardMember::create(array_merge($request->all(), ['tenant_id' => $tenant_id]));
        
        return back()->with('success', 'Membro da diretoria adicionado!');
    }

    public function deleteBoardMember($id)
    {
        BoardMember::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id)->delete();
        return back()->with('success', 'Membro removido.');
    }

    public function addDocument(Request $request)
    {
        $request->validate(['title' => 'required', 'type' => 'required', 'file' => 'required|file|mimes:pdf,jpg,png,zip|max:5120']);
        $tenant_id = auth()->user()->tenant_id;

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('transparency_docs', 'public');
            
            TransparencyDocument::create([
                'tenant_id' => $tenant_id,
                'title' => $request->title,
                'type' => $request->type,
                'file_path' => $path,
                'year' => $request->year,
                'document_date' => $request->document_date
            ]);
        }

        return back()->with('success', 'Documento postado com sucesso!');
    }

    public function deleteDocument($id)
    {
        TransparencyDocument::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id)->delete();
        return back()->with('success', 'Documento removido.');
    }

    public function addPartnership(Request $request)
    {
        $request->validate(['agency_name' => 'required', 'project_name' => 'required', 'value' => 'required']);
        $tenant_id = auth()->user()->tenant_id;
        
        PublicPartnership::create(array_merge($request->all(), ['tenant_id' => $tenant_id]));
        
        return back()->with('success', 'Parceria registrada!');
    }

    public function deletePartnership($id)
    {
        PublicPartnership::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id)->delete();
        return back()->with('success', 'Parceria removida.');
    }

    /**
     * Public View of the Transparency Portal
     */
    public function renderPortal($slug)
    {
        $portal = TransparencyPortal::where('slug', $slug)->where('is_published', true)->firstOrFail();
        $tenant_id = $portal->tenant_id;

        // Metadata
        $board = BoardMember::where('tenant_id', $tenant_id)->get();
        $docs = TransparencyDocument::where('tenant_id', $tenant_id)->get()->groupBy('type');
        $partnerships = PublicPartnership::where('tenant_id', $tenant_id)->get();

        // Financial Data (Aggregated from Transactions)
        $totalIn = Transaction::where('tenant_id', $tenant_id)->where('type', 'income')->sum('amount');
        $totalOut = Transaction::where('tenant_id', $tenant_id)->where('type', 'expense')->sum('amount');
        $investmentSocial = Transaction::where('tenant_id', $tenant_id)->where('type', 'expense')->sum('amount'); // Simplificado
        $balance = $totalIn - $totalOut;

        $lastExpenses = Transaction::where('tenant_id', $tenant_id)->where('type', 'expense')->orderBy('date', 'desc')->limit(5)->get();

        // Social Impact (Aggregated)
        $familiesCount = Beneficiary::where('tenant_id', $tenant_id)->count();
        $peopleCount = $familiesCount * 3.5; // Estimativa baseada em membros da família
        $attendancesCount = \DB::table('attendances')->where('tenant_id', $tenant_id)->whereMonth('date', now()->month)->count();
        
        $assets = Asset::where('tenant_id', $tenant_id)->get();
        
        // HR
        $employees = Employee::where('tenant_id', $tenant_id)->get();

        return view('transparency.portal', compact(
            'portal', 'board', 'docs', 'partnerships', 
            'totalIn', 'totalOut', 'investmentSocial', 'balance', 'lastExpenses',
            'familiesCount', 'peopleCount', 'attendancesCount', 'assets', 'employees'
        ));
    }
}
