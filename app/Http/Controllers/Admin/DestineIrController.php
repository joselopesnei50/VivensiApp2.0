<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DestineIrMeetingSignup;
use App\Models\DestineIrPartner;
use App\Models\DestineIrSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DestineIrController extends Controller
{
    public function edit()
    {
        $settings = DestineIrSetting::current();
        $partners = DestineIrPartner::orderBy('sort_order')->orderBy('name')->get();
        return view('admin.destine-ir.edit', compact('settings', 'partners'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_published'           => 'nullable|boolean',
            'hero_title'             => 'required|string|max:255',
            'hero_subtitle'          => 'required|string|max:500',
            'deadline_label'         => 'nullable|string|max:120',
            'deadline_date'          => 'nullable|date',
            'fund_name'              => 'required|string|max:255',
            'fund_cnpj'              => 'required|string|max:32',
            'fund_email'             => 'required|email|max:160',
            'council_name'           => 'required|string|max:255',
            'what_is_text'           => 'nullable|string',
            'who_can_text'           => 'nullable|string',
            'path1_steps'            => 'nullable|array',
            'path1_steps.*.title'    => 'nullable|string|max:160',
            'path1_steps.*.text'     => 'nullable|string|max:2000',
            'path2_steps'            => 'nullable|array',
            'path2_steps.*.title'    => 'nullable|string|max:160',
            'path2_steps.*.text'     => 'nullable|string|max:2000',
            'cost_text'              => 'nullable|string|max:2000',
            'faq_items'              => 'nullable|array',
            'faq_items.*.q'          => 'nullable|string|max:300',
            'faq_items.*.a'          => 'nullable|string|max:2000',
            'footer_tagline'         => 'nullable|string|max:300',
            'footer_legal_text'      => 'nullable|string',
            'og_title'               => 'nullable|string|max:255',
            'og_description'         => 'nullable|string|max:500',
            'video_youtube_url'      => 'nullable|url|max:500',
            'show_deadline_banner'   => 'nullable|boolean',
            'show_simulator'         => 'nullable|boolean',
            'show_partners_wall'     => 'nullable|boolean',
            'show_share_bar'         => 'nullable|boolean',
            'show_meeting_cta'       => 'nullable|boolean',
            'meeting_title'          => 'nullable|string|max:255',
            'meeting_description'    => 'nullable|string|max:500',
        ]);

        $settings = DestineIrSetting::current();
        $settings->fill(array_merge(
            $data,
            [
                'is_published'         => (bool) $request->boolean('is_published'),
                'show_deadline_banner' => (bool) $request->boolean('show_deadline_banner'),
                'show_simulator'       => (bool) $request->boolean('show_simulator'),
                'show_partners_wall'   => (bool) $request->boolean('show_partners_wall'),
                'show_share_bar'       => (bool) $request->boolean('show_share_bar'),
                'show_meeting_cta'     => (bool) $request->boolean('show_meeting_cta'),
            ]
        ));

        // Limpa itens vazios dos repeaters
        foreach (['path1_steps', 'path2_steps', 'faq_items'] as $key) {
            $value = $settings->{$key};
            if (is_array($value)) {
                $settings->{$key} = array_values(array_filter($value, fn ($item) => is_array($item) && array_filter($item, fn ($v) => trim((string) $v) !== '')));
            }
        }

        $settings->save();

        return back()->with('status', 'Página atualizada.');
    }

    public function uploadVideo(Request $request): RedirectResponse
    {
        $request->validate([
            'orientation' => 'required|in:vertical,horizontal',
            'video'       => 'required|file|mimetypes:video/mp4,video/quicktime|max:51200', // 50MB
        ]);

        $settings = DestineIrSetting::current();
        $disk = Storage::disk('public');
        $orientation = $request->input('orientation');
        $field = $orientation === 'vertical' ? 'video_vertical_url' : 'video_horizontal_url';

        $ext = strtolower((string) $request->file('video')->extension()) ?: 'mp4';
        $name = 'destine-ir-' . $orientation . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;
        $path = $disk->putFileAs('destine-ir', $request->file('video'), $name);

        $settings->{$field} = $disk->url($path);
        $settings->save();

        return back()->with('status', 'Vídeo ' . $orientation . ' atualizado.');
    }

    public function uploadPoster(Request $request): RedirectResponse
    {
        $request->validate([
            'poster' => 'required|image|max:4096',
        ]);

        $settings = DestineIrSetting::current();
        $disk = Storage::disk('public');
        $ext = strtolower((string) $request->file('poster')->extension()) ?: 'jpg';
        $name = 'destine-ir-poster-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;
        $path = $disk->putFileAs('destine-ir', $request->file('poster'), $name);
        $settings->video_poster_url = $disk->url($path);
        $settings->save();

        return back()->with('status', 'Capa do vídeo atualizada.');
    }

    public function uploadOgImage(Request $request): RedirectResponse
    {
        $request->validate([
            'og_image' => 'required|image|max:4096',
        ]);

        $settings = DestineIrSetting::current();
        $disk = Storage::disk('public');
        $ext = strtolower((string) $request->file('og_image')->extension()) ?: 'jpg';
        $name = 'destine-ir-og-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;
        $path = $disk->putFileAs('destine-ir', $request->file('og_image'), $name);
        $settings->og_image_url = $disk->url($path);
        $settings->save();

        return back()->with('status', 'Imagem de compartilhamento atualizada.');
    }

    // ── Parceiras ────────────────────────────────────────────────────────────

    public function storePartner(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'slug'          => 'nullable|string|max:120',
            'contact_name'  => 'nullable|string|max:160',
            'contact_phone' => 'nullable|string|max:32',
            'contact_email' => 'nullable|email|max:160',
            'sort_order'    => 'nullable|integer|min:0|max:9999',
            'is_active'     => 'nullable|boolean',
            'logo'          => 'nullable|image|max:2048',
        ]);

        $partner = new DestineIrPartner();
        $partner->name          = $data['name'];
        $partner->slug          = DestineIrPartner::makeSlug(($data['slug'] ?? '') ?: $data['name']);
        $partner->contact_name  = $data['contact_name'] ?? null;
        $partner->contact_phone = $data['contact_phone'] ?? null;
        $partner->contact_email = $data['contact_email'] ?? null;
        $partner->sort_order    = (int) ($data['sort_order'] ?? 0);
        $partner->is_active     = $request->boolean('is_active', true);

        if ($request->hasFile('logo')) {
            $this->storePartnerLogo($partner, $request->file('logo'));
        }

        $partner->save();

        return back()->with('status', 'Entidade cadastrada.');
    }

    public function updatePartner(Request $request, DestineIrPartner $partner): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'slug'          => 'nullable|string|max:120',
            'contact_name'  => 'nullable|string|max:160',
            'contact_phone' => 'nullable|string|max:32',
            'contact_email' => 'nullable|email|max:160',
            'sort_order'    => 'nullable|integer|min:0|max:9999',
            'is_active'     => 'nullable|boolean',
            'logo'          => 'nullable|image|max:2048',
        ]);

        $partner->name          = $data['name'];
        if (!empty($data['slug']) && $data['slug'] !== $partner->slug) {
            $partner->slug = DestineIrPartner::makeSlug($data['slug'], $partner->id);
        }
        $partner->contact_name  = $data['contact_name'] ?? null;
        $partner->contact_phone = $data['contact_phone'] ?? null;
        $partner->contact_email = $data['contact_email'] ?? null;
        $partner->sort_order    = (int) ($data['sort_order'] ?? 0);
        $partner->is_active     = $request->boolean('is_active');

        if ($request->hasFile('logo')) {
            $this->storePartnerLogo($partner, $request->file('logo'));
        }

        $partner->save();

        return back()->with('status', 'Entidade atualizada.');
    }

    public function destroyPartner(DestineIrPartner $partner): RedirectResponse
    {
        if ($partner->logo_path) {
            try { Storage::disk('public')->delete($partner->logo_path); } catch (\Throwable $e) {}
        }
        $partner->delete();
        return back()->with('status', 'Entidade removida.');
    }

    public function metrics()
    {
        $settings = DestineIrSetting::current();
        $partners = DestineIrPartner::orderByDesc('visits_count')->orderBy('name')->get();
        return view('admin.destine-ir.metrics', compact('settings', 'partners'));
    }

    public function signups(Request $request)
    {
        $filter = $request->query('status', 'all');
        $query = DestineIrMeetingSignup::with('partner')->orderByDesc('created_at');

        if ($filter === 'pending') {
            $query->whereNull('notified_at');
        } elseif ($filter === 'notified') {
            $query->whereNotNull('notified_at');
        }

        $signups = $query->paginate(50)->withQueryString();
        $totals  = [
            'all'      => DestineIrMeetingSignup::count(),
            'pending'  => DestineIrMeetingSignup::whereNull('notified_at')->count(),
            'notified' => DestineIrMeetingSignup::whereNotNull('notified_at')->count(),
        ];

        return view('admin.destine-ir.signups', compact('signups', 'filter', 'totals'));
    }

    public function signupsCsv(): StreamedResponse
    {
        $filename = 'destine-ir-signups-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 pra Excel abrir sem bagunça
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data', 'Nome', 'E-mail', 'WhatsApp', 'Entidade de origem', 'Notificado em', 'Observação']);

            DestineIrMeetingSignup::with('partner')->orderByDesc('created_at')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        optional($r->created_at)->format('d/m/Y H:i'),
                        $r->name,
                        $r->email,
                        $r->phone,
                        optional($r->partner)->name,
                        optional($r->notified_at)->format('d/m/Y H:i'),
                        $r->notes,
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function markSignupNotified(DestineIrMeetingSignup $signup): RedirectResponse
    {
        $signup->notified_at = $signup->notified_at ? null : now();
        $signup->save();
        return back()->with('status', $signup->notified_at ? 'Marcado como notificado.' : 'Desmarcado.');
    }

    private function storePartnerLogo(DestineIrPartner $partner, \Illuminate\Http\UploadedFile $file): void
    {
        $disk = Storage::disk('public');
        if ($partner->logo_path) {
            try { $disk->delete($partner->logo_path); } catch (\Throwable $e) {}
        }
        $ext = strtolower((string) $file->extension()) ?: 'png';
        $name = 'logo-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;
        $path = $disk->putFileAs('destine-ir/partners', $file, $name);
        $partner->logo_path = $path;
        $partner->logo_url  = $disk->url($path);
    }
}
