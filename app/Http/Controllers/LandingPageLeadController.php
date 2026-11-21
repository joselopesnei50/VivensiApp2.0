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

        $labels = $this->customLabels($page);
        foreach ($leads as $lead) {
            $lead->extra_items = $this->extraItems($lead->extra_data ?? null, $labels);
        }

        return view('ngo.landing_pages.leads', compact('page', 'leads'));
    }

    /** Rótulos dos campos personalizados dos formulários da página (chave => rótulo). */
    private function customLabels(\App\Models\LandingPage $page): array
    {
        $labels = [];
        $sections = $page->sections()->whereIn('type', ['lead_capture', 'final_cta_form'])->get(['content']);
        foreach ($sections as $section) {
            foreach ((array) ($section->content['custom_fields'] ?? []) as $f) {
                if (!is_array($f) || empty($f['label'])) continue;
                $key = trim(preg_replace('/[^a-z0-9_]+/', '_', mb_strtolower(trim((string) ($f['key'] ?? '')))), '_');
                if ($key !== '' && !isset($labels[$key])) $labels[$key] = (string) $f['label'];
            }
        }
        return $labels;
    }

    /**
     * Converte o JSON de extra_data em pares legíveis [rótulo => valor].
     * Ignora chaves internas (source, campos começando com "_").
     */
    private function extraItems(?string $json, array $labels): array
    {
        $data = json_decode((string) $json, true);
        if (!is_array($data)) {
            return ($json !== null && trim($json) !== '' && trim($json) !== '[]') ? ['' => $json] : [];
        }

        $items = [];
        foreach ((array) ($data['custom'] ?? []) as $key => $value) {
            if ($value === null || $value === '') continue;
            $items[$labels[$key] ?? \Illuminate\Support\Str::headline((string) $key)] = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        unset($data['custom']);
        foreach ($data as $key => $value) {
            if ($key === 'source' || str_starts_with((string) $key, '_') || $value === null || $value === '') continue;
            $items[\Illuminate\Support\Str::headline((string) $key)] = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return $items;
    }

    private function extraText(array $items): string
    {
        return collect($items)->map(fn ($v, $k) => $k === '' ? $v : "{$k}: {$v}")->implode('; ');
    }

    public function exportCsv($id)
    {
        $page = \App\Models\LandingPage::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        $filename = 'leads-' . \Illuminate\Support\Str::slug($page->title) . '-' . date('Y-m-d_His') . '.csv';

        AuditDownload::log('LandingPage:Leads', (int) $page->id, [
            'format' => 'csv',
            'slug' => $page->slug,
        ]);

        $labels = $this->customLabels($page);

        return response()->streamDownload(function () use ($page, $labels) {
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
                    ->chunk(500, function ($rows) use ($out, $labels) {
                        foreach ($rows as $lead) {
                            $created = $lead->created_at ? (string) $lead->created_at : '';
                            $name = $lead->name ?? '';
                            $email = $lead->email ?? '';
                            $phone = $lead->phone ?? '';
                            $extra = $this->extraText($this->extraItems($lead->extra_data ?? null, $labels));

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
