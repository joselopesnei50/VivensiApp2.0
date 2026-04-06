<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Support\AuditDownload;

class LandingPageLeadController extends Controller
{
    public function index($id)
    {
        $page = \App\Models\LandingPage::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $leads = DB::table('landing_page_leads')
                    ->where('landing_page_id', $page->id)
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);

        return view('ngo.landing_pages.leads', compact('page', 'leads'));
    }

    public function exportCsv($id)
    {
        $page = \App\Models\LandingPage::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        $filename = 'leads-' . \Illuminate\Support\Str::slug($page->title) . '-' . date('Y-m-d_His') . '.csv';

        AuditDownload::log('LandingPage:Leads', (int) $page->id, [
            'format' => 'csv',
            'slug' => $page->slug,
        ]);

        return response()->streamDownload(function () use ($page) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            // UTF-8 BOM for Excel compatibility.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Data', 'Nome', 'E-mail', 'WhatsApp', 'Dados extras']);

            try {
                DB::table('landing_page_leads')
                    ->where('landing_page_id', $page->id)
                    ->orderBy('created_at', 'desc')
                    ->chunk(500, function ($rows) use ($out) {
                        foreach ($rows as $lead) {
                            $created = $lead->created_at ? (string) $lead->created_at : '';
                            $name = $lead->name ?? '';
                            $email = $lead->email ?? '';
                            $phone = $lead->phone ?? '';
                            $extra = $lead->extra_data ?? '';

                            fputcsv($out, [$created, $name, $email, $phone, $extra]);
                        }
                    });
            } catch (\Throwable $e) {
                Log::warning('LandingPage: failed exporting leads csv', [
                    'page_id' => $page->id,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                fclose($out);
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
