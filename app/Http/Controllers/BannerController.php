<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\BannerSection;
use App\Models\ScheduledPost;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use DB;

class BannerController extends Controller
{
    private function checkAccess(): void
    {
        Gate::authorize('access-whatsapp');
    }

    public function index()
    {
        $this->checkAccess();
        $banners = Banner::latest()->get();
        $formats = Banner::formats();
        return view('banners.index', compact('banners', 'formats'));
    }

    public function store(Request $request)
    {
        $this->checkAccess();
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'format'        => 'required|in:' . implode(',', array_keys(Banner::formats())),
            'custom_width'  => 'nullable|integer|min:100|max:4000',
            'custom_height' => 'nullable|integer|min:100|max:4000',
        ]);

        $fmt    = Banner::formats()[$data['format']];
        $width  = $data['format'] === 'custom' ? ($data['custom_width']  ?? $fmt['w']) : $fmt['w'];
        $height = $data['format'] === 'custom' ? ($data['custom_height'] ?? $fmt['h']) : $fmt['h'];

        $banner = Banner::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id'   => auth()->id(),
            'title'     => $data['title'],
            'format'    => $data['format'],
            'width'     => $width,
            'height'    => $height,
            'settings'  => ['font_family' => 'Inter', 'bg_color' => '#ffffff'],
        ]);

        return redirect()->route('banners.canvas', $banner)
            ->with('success', 'Banner criado! Comece a editar no canvas.');
    }

    public function builder(Banner $banner)
    {
        $this->checkAccess();
        Gate::authorize('update', $banner);
        $banner->load('sections');
        $sectionTypes   = $this->getSectionTypes();
        $scheduledPosts = ScheduledPost::where('status', 'scheduled')->get();
        $formats        = Banner::formats();
        return view('banners.builder', compact('banner', 'sectionTypes', 'scheduledPosts', 'formats'));
    }

    public function addSection(Request $request, Banner $banner)
    {
        $this->checkAccess();
        Gate::authorize('update', $banner);

        $type     = $request->input('type');
        $maxOrder = $banner->sections()->max('sort_order') ?? -1;

        BannerSection::create([
            'banner_id'  => $banner->id,
            'type'       => $type,
            'content'    => $this->getDefaultContent($type),
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('banners.builder', $banner);
    }

    public function updateSection(Request $request, BannerSection $section)
    {
        $this->checkAccess();
        Gate::authorize('update', $section->banner);

        $raw     = $request->except(['_token', '_method']);
        $cleaned = [];
        foreach ($raw as $k => $v) {
            $cleaned[$k] = is_numeric($v) ? ($v + 0) : $v;
        }
        $section->update(['content' => $cleaned]);
        $section->load('banner');

        $html = view('banners.partials.section_html', [
            'c'      => $section->content,
            'type'   => $section->type,
            'banner' => $section->banner,
        ])->render();

        return response()->json(['ok' => true, 'html' => $html]);
    }

    public function deleteSection(BannerSection $section)
    {
        $this->checkAccess();
        Gate::authorize('update', $section->banner);
        $section->delete();
        return response()->json(['ok' => true]);
    }

    public function applyTemplate(Request $request, Banner $banner)
    {
        $this->checkAccess();
        Gate::authorize('update', $banner);

        $sections = $request->input('sections', []);

        $banner->sections()->delete();

        foreach ($sections as $i => $s) {
            BannerSection::create([
                'banner_id'  => $banner->id,
                'type'       => $s['type'],
                'content'    => $s['content'],
                'sort_order' => $i,
            ]);
        }

        $banner->load('sections');
        $html = '';
        foreach ($banner->sections as $section) {
            $html .= view('banners.partials.section_render', [
                'section' => $section,
                'banner'  => $banner,
            ])->render();
        }

        return response()->json(['ok' => true, 'html' => $html]);
    }

    public function updateSettings(Request $request, Banner $banner)
    {
        $this->checkAccess();
        Gate::authorize('update', $banner);

        $v = $request->validate([
            'title'         => 'sometimes|string|max:255',
            'format'        => 'sometimes|in:' . implode(',', array_keys(Banner::formats())),
            'custom_width'  => 'sometimes|integer|min:100|max:4000',
            'custom_height' => 'sometimes|integer|min:100|max:4000',
            'font_family'   => 'sometimes|string|max:100',
            'bg_color'      => 'sometimes|string|max:50',
            'scheduled_post_id' => 'nullable|integer',
        ]);

        if (isset($v['title']))  $banner->title = $v['title'];

        if (isset($v['format'])) {
            $fmt = Banner::formats()[$v['format']];
            $banner->format = $v['format'];
            $banner->width  = $v['format'] === 'custom' ? ($v['custom_width']  ?? $fmt['w']) : $fmt['w'];
            $banner->height = $v['format'] === 'custom' ? ($v['custom_height'] ?? $fmt['h']) : $fmt['h'];
        }

        $settings = $banner->settings ?? [];
        if (isset($v['font_family'])) $settings['font_family'] = $v['font_family'];
        if (isset($v['bg_color']))    $settings['bg_color']    = $v['bg_color'];
        $banner->settings = $settings;

        if (array_key_exists('scheduled_post_id', $v)) {
            $banner->scheduled_post_id = $v['scheduled_post_id'] ?: null;
        }

        $banner->save();

        return response()->json([
            'ok'     => true,
            'width'  => $banner->width,
            'height' => $banner->height,
            'title'  => $banner->title,
        ]);
    }

    public function destroy(Banner $banner)
    {
        $this->checkAccess();
        Gate::authorize('update', $banner);
        $banner->delete();
        return redirect()->route('banners.index')->with('success', 'Banner removido.');
    }

    public function duplicate(Banner $banner)
    {
        $this->checkAccess();
        Gate::authorize('update', $banner);

        $new = $banner->replicate(['scheduled_post_id', 'thumbnail']);
        $new->title = $banner->title . ' (cópia)';
        $new->save();

        foreach ($banner->sections as $section) {
            $s = $section->replicate();
            $s->banner_id = $new->id;
            $s->save();
        }

        return redirect()->route('banners.builder', $new)->with('success', 'Banner duplicado!');
    }

    public function preview(Banner $banner)
    {
        Gate::authorize('update', $banner);
        $banner->load('sections');
        return view('banners.render', compact('banner'));
    }

    public function exportHtml(Banner $banner)
    {
        Gate::authorize('update', $banner);
        $banner->load('sections');
        $html = view('banners.render', compact('banner'))->render();
        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="banner-' . $banner->id . '.html"');
    }

    /** Fabric.js canvas editor */
    public function canvas(Banner $banner)
    {
        $this->checkAccess();
        Gate::authorize('update', $banner);
        $formats = Banner::formats();
        $socialAccounts = SocialAccount::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)->get();
        return view('banners.canvas', compact('banner', 'formats', 'socialAccounts'));
    }

    /** Save Fabric.js JSON + PNG thumbnail */
    public function saveFabric(Request $request, Banner $banner)
    {
        $this->checkAccess();
        Gate::authorize('update', $banner);

        $v = $request->validate([
            'fabric_json' => 'required|string',
            'png_data'    => 'nullable|string', // base64 data URL
            'title'       => 'sometimes|string|max:255',
        ]);

        if (isset($v['title'])) $banner->title = $v['title'];
        $banner->fabric_json = $v['fabric_json'];

        if (!empty($v['png_data'])) {
            $dataUrl = $v['png_data'];
            if (str_starts_with($dataUrl, 'data:image/png;base64,')) {
                $base64 = substr($dataUrl, strlen('data:image/png;base64,'));
                $bytes  = base64_decode($base64, true);
                // Validate PNG magic bytes: \x89PNG\r\n\x1a\n
                if ($bytes !== false && strlen($bytes) > 8 && substr($bytes, 0, 8) === "\x89PNG\r\n\x1a\n") {
                    $path = 'banners/thumbnails/banner_' . $banner->id . '_' . time() . '.png';
                    Storage::disk('public')->put($path, $bytes);
                    $banner->png_path = $path;
                }
            }
        }

        $banner->save();
        return response()->json(['ok' => true, 'title' => $banner->title]);
    }

    /** Upload image to storage, return URL */
    public function uploadImage(Request $request, Banner $banner)
    {
        $this->checkAccess();
        Gate::authorize('update', $banner);

        $request->validate(['image' => 'required|image|max:10240']);
        $path = $request->file('image')->store('banners/uploads', 'public');
        return response()->json(['ok' => true, 'url' => Storage::disk('public')->url($path)]);
    }

    /** Create a draft ScheduledPost from the canvas banner */
    public function scheduleFromCanvas(Request $request, Banner $banner)
    {
        $this->checkAccess();
        Gate::authorize('update', $banner);

        $v = $request->validate([
            'social_account_id' => 'required|integer',
            'platform'          => 'required|string|max:50',
            'caption'           => 'required|string|max:2200',
            'scheduled_at'      => 'required|date|after:now',
            'png_data'          => 'nullable|string',
        ]);

        $mediaUrl = null;
        if (!empty($v['png_data']) && str_starts_with($v['png_data'], 'data:image/png;base64,')) {
            $base64 = substr($v['png_data'], strlen('data:image/png;base64,'));
            $bytes  = base64_decode($base64, true);
            // Validate PNG magic bytes: \x89PNG\r\n\x1a\n
            if ($bytes !== false && strlen($bytes) > 8 && substr($bytes, 0, 8) === "\x89PNG\r\n\x1a\n") {
                $path     = 'banners/scheduled/banner_' . $banner->id . '_' . time() . '.png';
                Storage::disk('public')->put($path, $bytes);
                $mediaUrl = Storage::disk('public')->url($path);
            }
        } elseif ($banner->png_path) {
            $mediaUrl = Storage::disk('public')->url($banner->png_path);
        }

        $post = ScheduledPost::create([
            'tenant_id'         => auth()->user()->tenant_id,
            'user_id'           => auth()->id(),
            'social_account_id' => $v['social_account_id'],
            'platform'          => $v['platform'],
            'caption'           => $v['caption'],
            'media_url'         => $mediaUrl,
            'media_type'        => 'image',
            'scheduled_at'      => $v['scheduled_at'],
            'status'            => 'scheduled',
        ]);

        $banner->scheduled_post_id = $post->id;
        $banner->save();

        return response()->json(['ok' => true, 'post_id' => $post->id]);
    }

    /** AI text generation via DeepSeek */
    public function generateAiText(Request $request, Banner $banner)
    {
        $this->checkAccess();
        Gate::authorize('update', $banner);

        $v = $request->validate([
            'prompt'    => 'required|string|max:500',
            'field'     => 'required|string|max:50',
        ]);

        $apiKey = DB::table('system_settings')->where('key', 'deepseek_api_key')->value('value');
        if (!$apiKey) {
            return response()->json(['ok' => false, 'error' => 'DeepSeek API key não configurada.'], 422);
        }

        $systemPrompt = 'Você é um especialista em copywriting para banners e redes sociais. Gere textos curtos, impactantes e criativos. Responda APENAS com o texto gerado, sem explicações ou formatação extra.';

        $resp = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
            ->timeout(20)
            ->post('https://api.deepseek.com/v1/chat/completions', [
                'model'    => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $v['prompt']],
                ],
                'max_tokens'  => 150,
                'temperature' => 0.85,
            ]);

        if (!$resp->successful()) {
            return response()->json(['ok' => false, 'error' => 'Erro na API de IA.'], 502);
        }

        $text = $resp->json('choices.0.message.content', '');
        return response()->json(['ok' => true, 'text' => trim($text)]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function getSectionTypes(): array
    {
        return [
            'hero_text'     => ['label' => 'Hero com Texto',    'icon' => 'fas fa-heading',      'desc' => 'Título + subtítulo + botão CTA sobre fundo sólido'],
            'image_overlay' => ['label' => 'Imagem + Overlay',  'icon' => 'fas fa-image',        'desc' => 'Imagem de fundo com texto e sobreposição de cor'],
            'gradient_text' => ['label' => 'Fundo Degradê',     'icon' => 'fas fa-paint-roller', 'desc' => 'Gradiente personalizado com texto e botão'],
            'split_banner'  => ['label' => 'Imagem + Texto',    'icon' => 'fas fa-table-columns', 'desc' => 'Layout dividido: imagem de um lado, texto do outro'],
            'announcement'  => ['label' => 'Anúncio / Alerta',  'icon' => 'fas fa-bullhorn',     'desc' => 'Ícone + tag + título + mensagem + botão'],
            'event_banner'  => ['label' => 'Card de Evento',    'icon' => 'fas fa-calendar-star','desc' => 'Data, horário, local e botão de inscrição'],
            'simple_text'   => ['label' => 'Texto Impactante',  'icon' => 'fas fa-font',         'desc' => 'Texto grande e impactante sobre fundo sólido'],
        ];
    }

    private function getDefaultContent(string $type): array
    {
        return match ($type) {
            'hero_text' => [
                'title'          => 'Título Principal do Banner',
                'subtitle'       => 'Mensagem de apoio clara e objetiva para sua campanha ou causa',
                'title_size'     => 52,
                'title_color'    => '#ffffff',
                'subtitle_size'  => 22,
                'subtitle_color' => '#e0e7ff',
                'bg_color'       => '#4f46e5',
                'alignment'      => 'center',
                'button_text'    => 'Saiba Mais',
                'button_url'     => '#',
                'button_bg'      => '#ffffff',
                'button_color'   => '#4f46e5',
                'button_radius'  => 8,
            ],
            'image_overlay' => [
                'image_url'       => 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?w=1200&q=80',
                'overlay_color'   => '#000000',
                'overlay_opacity' => 55,
                'title'           => 'Faça a Diferença',
                'subtitle'        => 'Sua participação transforma vidas e comunidades inteiras',
                'title_size'      => 56,
                'title_color'     => '#ffffff',
                'subtitle_size'   => 22,
                'subtitle_color'  => '#f1f5f9',
                'alignment'       => 'center',
                'button_text'     => 'Participar',
                'button_url'      => '#',
                'button_bg'       => '#ffffff',
                'button_color'    => '#1e293b',
                'button_radius'   => 50,
            ],
            'gradient_text' => [
                'gradient_from'  => '#7C3AED',
                'gradient_to'    => '#3B82F6',
                'gradient_angle' => 135,
                'title'          => 'Transformando Vidas',
                'subtitle'       => 'Sua contribuição constrói um futuro melhor para todos',
                'title_size'     => 54,
                'subtitle_size'  => 22,
                'title_color'    => '#ffffff',
                'subtitle_color' => '#e0e7ff',
                'alignment'      => 'center',
                'button_text'    => 'Contribuir Agora',
                'button_url'     => '#',
                'button_bg'      => 'rgba(255,255,255,0.15)',
                'button_color'   => '#ffffff',
                'button_radius'  => 8,
            ],
            'split_banner' => [
                'image_url'      => 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?w=800&q=80',
                'image_side'     => 'left',
                'bg_color'       => '#ffffff',
                'tag'            => 'CAMPANHA',
                'tag_bg'         => '#4f46e5',
                'tag_color'      => '#ffffff',
                'title'          => 'Unidos por uma Causa',
                'subtitle'       => 'Acreditamos que cada ação conta. Junte-se a nós e ajude a transformar comunidades.',
                'title_size'     => 38,
                'title_color'    => '#111827',
                'subtitle_size'  => 17,
                'subtitle_color' => '#6b7280',
                'button_text'    => 'Quero Participar',
                'button_url'     => '#',
                'button_bg'      => '#4f46e5',
                'button_color'   => '#ffffff',
                'button_radius'  => 8,
            ],
            'announcement' => [
                'icon'          => 'fas fa-bullhorn',
                'icon_color'    => '#f59e0b',
                'bg_color'      => '#fffbeb',
                'tag'           => 'NOVIDADE',
                'tag_bg'        => '#f59e0b',
                'tag_color'     => '#ffffff',
                'title'         => 'Importante Anúncio',
                'message'       => 'Temos uma novidade incrível para compartilhar com você. Fique por dentro de tudo!',
                'title_size'    => 36,
                'title_color'   => '#111827',
                'message_size'  => 18,
                'message_color' => '#6b7280',
                'button_text'   => 'Ver Detalhes',
                'button_url'    => '#',
                'button_bg'     => '#f59e0b',
                'button_color'  => '#ffffff',
                'button_radius' => 8,
            ],
            'event_banner' => [
                'bg_color'      => '#0f172a',
                'accent_color'  => '#6366f1',
                'tag'           => 'EVENTO',
                'title'         => 'Nome do Evento',
                'date'          => '15 de Julho de 2025',
                'time'          => '19h00',
                'location'      => 'Online / Presencial',
                'title_size'    => 46,
                'title_color'   => '#ffffff',
                'date_color'    => '#a5b4fc',
                'button_text'   => 'Inscrever-se',
                'button_url'    => '#',
                'button_bg'     => '#6366f1',
                'button_color'  => '#ffffff',
                'button_radius' => 8,
                'logo_url'      => '',
            ],
            'simple_text' => [
                'bg_color'    => '#f8fafc',
                'text'        => 'Sua mensagem aqui',
                'text_size'   => 80,
                'text_color'  => '#111827',
                'text_weight' => 800,
                'alignment'   => 'center',
            ],
            default => [],
        };
    }
}
