<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\LandingPageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LandingPageController extends Controller
{
    private function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;

        // Slug is globally unique in DB; add suffix with retry.
        $i = 0;
        while (LandingPage::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $i++;
            $slug = $base . '-' . Str::random(6);
            if ($i > 10) {
                $slug = $base . '-' . now()->format('YmdHis') . '-' . Str::random(4);
            }
        }

        return $slug;
    }

    private function canViewDraft(LandingPage $page): bool
    {
        return auth()->check() && (auth()->user()->tenant_id === $page->tenant_id);
    }

    public function index()
    {
        $pages = LandingPage::where('tenant_id', auth()->user()->tenant_id)->get();
        $routeName = optional(request()->route())->getName();
        $context = $routeName === 'manager.landing_pages' ? 'manager' : 'ngo';
        return view('ngo.landing_pages.index', compact('pages', 'context'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $title = trim((string) $request->title);

        $page = LandingPage::create([
            'tenant_id' => auth()->user()->tenant_id,
            'title' => $title,
            'slug' => $this->generateUniqueSlug($title),
            'status' => 'draft',
            'settings' => [
                'theme_color' => '#4f46e5',
                'font_family' => 'Inter',
                // SEO defaults (editable in builder)
                'seo_title' => $title,
                'seo_description' => 'Saiba mais sobre esta campanha e participe. Transparência, impacto e ação.',
                'og_image_url' => '',
                'favicon_url' => '',
            ]
        ]);

        return redirect()->route('landing-pages.builder', $page->id);
    }

    public function builder($id)
    {
        $page = LandingPage::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $sections = $page->sections;
        
        return view('ngo.landing_pages.builder', compact('page', 'sections'));
    }

    public function updateSettings(Request $request, $id)
    {
        $page = LandingPage::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'theme_color' => ['nullable', 'string', 'max:30', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'seo_title' => 'nullable|string|max:120',
            'seo_description' => 'nullable|string|max:255',
            'og_image_url' => 'nullable|string|max:2048',
            'favicon_url' => 'nullable|string|max:2048',
        ]);

        if (array_key_exists('title', $validated) && $validated['title'] !== null) {
            $page->title = trim((string) $validated['title']);
        }

        $settings = is_array($page->settings) ? $page->settings : [];
        if (array_key_exists('theme_color', $validated) && $validated['theme_color'] !== null) {
            $settings['theme_color'] = $validated['theme_color'];
        }
        if (array_key_exists('seo_title', $validated) && $validated['seo_title'] !== null) {
            $settings['seo_title'] = trim((string) $validated['seo_title']);
        }
        if (array_key_exists('seo_description', $validated) && $validated['seo_description'] !== null) {
            $settings['seo_description'] = trim((string) $validated['seo_description']);
        }
        if (array_key_exists('og_image_url', $validated)) {
            $settings['og_image_url'] = trim((string) ($validated['og_image_url'] ?? ''));
        }
        if (array_key_exists('favicon_url', $validated)) {
            $settings['favicon_url'] = trim((string) ($validated['favicon_url'] ?? ''));
        }

        // Reasonable fallbacks (avoid empty SEO title).
        if (empty($settings['seo_title'])) {
            $settings['seo_title'] = $page->title;
        }

        $page->settings = $settings;
        $page->save();

        return response()->json([
            'success' => true,
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => $page->status,
                'settings' => $page->settings,
            ],
        ]);
    }

    public function uploadOgImage(Request $request, $id)
    {
        $page = LandingPage::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        $validated = $request->validate([
            'file' => 'required|file|image|max:4096', // 4MB
        ]);

        $file = $validated['file'];
        $res = $this->storeLpAsset($page, $file, 'og');

        return response()->json([
            'success' => true,
            'url' => $res['url'],
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => $page->status,
                'settings' => $page->settings,
            ],
        ]);
    }

    public function uploadFavicon(Request $request, $id)
    {
        $page = LandingPage::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        $validated = $request->validate([
            'file' => 'required|file|image|max:512', // 512KB
        ]);

        $file = $validated['file'];
        $res = $this->storeLpAsset($page, $file, 'favicon');

        return response()->json([
            'success' => true,
            'url' => $res['url'],
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => $page->status,
                'settings' => $page->settings,
            ],
        ]);
    }

    private function storeLpAsset(LandingPage $page, \Illuminate\Http\UploadedFile $file, string $kind): array
    {
        $disk = Storage::disk('public');

        $settings = is_array($page->settings) ? $page->settings : [];
        $oldPathKey = $kind === 'favicon' ? 'favicon_path' : 'og_image_path';
        $oldUrlKey = $kind === 'favicon' ? 'favicon_url' : 'og_image_url';

        // Delete old file if we stored it previously.
        $oldPath = (string) ($settings[$oldPathKey] ?? '');
        if ($oldPath !== '') {
            try {
                $disk->delete($oldPath);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $ext = strtolower((string) $file->extension());
        if ($ext === '') {
            $ext = 'png';
        }

        $dir = 'landing-pages/' . $page->tenant_id . '/' . $page->id;
        $name = $kind . '-' . now()->format('YmdHis') . '-' . Str::random(8) . '.' . $ext;
        $path = $disk->putFileAs($dir, $file, $name);

        $url = $disk->url($path);

        if ($kind === 'favicon') {
            $settings['favicon_url'] = $url;
            $settings['favicon_path'] = $path;
        } else {
            $settings['og_image_url'] = $url;
            $settings['og_image_path'] = $path;
        }

        // Keep empty string out (paranoia).
        if (($settings[$oldUrlKey] ?? '') === null) {
            $settings[$oldUrlKey] = '';
        }

        $page->settings = $settings;
        $page->save();

        return ['path' => $path, 'url' => $url];
    }

    public function addSection(Request $request, $id)
    {
        $page = LandingPage::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        
        $type = $request->type;
        $defaultContent = $this->getDefaultContent($type);

        $section = $page->sections()->create([
            'type' => $type,
            'content' => $defaultContent,
            'sort_order' => $page->sections()->count() + 1
        ]);

        return response()->json($section);
    }

    public function updateSection(Request $request, $id)
    {
        $section = LandingPageSection::findOrFail($id);
        // Verify ownership via page
        if ($section->landingPage->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $section->update(['content' => $request->content]);

        return response()->json(['success' => true]);
    }

    public function deleteSection($id)
    {
        $section = LandingPageSection::findOrFail($id);
        if ($section->landingPage->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
        $section->delete();
        return response()->json(['success' => true]);
    }

    public function updateOrder(Request $request)
    {
        foreach ($request->order as $item) {
            LandingPageSection::where('id', $item['id'])->update(['sort_order' => $item['position']]);
        }
        return response()->json(['success' => true]);
    }

    public function renderPage($slug)
    {
        // Public route: ignore tenant scopes. Access is controlled by status and ownership below.
        $page = LandingPage::withoutGlobalScopes()->where('slug', $slug)->firstOrFail();
        if (($page->status ?? 'draft') !== 'published' && !$this->canViewDraft($page)) {
            abort(404);
        }
        $sections = $page->sections;
        
        return view('landing_pages.render', compact('page', 'sections'));
    }

    public function sitemap()
    {
        $pages = LandingPage::withoutGlobalScopes()
            ->where('status', 'published')
            ->orderBy('updated_at', 'desc')
            ->get(['slug', 'updated_at']);

        $base = url('/');
        $now = now()->toAtomString();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($pages as $p) {
            $loc = $base . '/lp/' . $p->slug;
            $lastmod = $p->updated_at ? $p->updated_at->toAtomString() : $now;

            $xml .= "  <url>\n";
            $xml .= '    <loc>' . e($loc) . "</loc>\n";
            $xml .= '    <lastmod>' . e($lastmod) . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= "</urlset>\n";

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    public function robots()
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin/',
            'Disallow: /ngo/',
            'Disallow: /manager/',
            'Disallow: /support/',
            'Disallow: /api/',
            'Allow: /lp/',
            'Allow: /lp-sitemap.xml',
            'Sitemap: ' . url('/lp-sitemap.xml'),
            '',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    public function publish($id)
    {
        $page = LandingPage::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $page->update(['status' => 'published']);
        return response()->json(['success' => true]);
    }

    public function unpublish($id)
    {
        $page = LandingPage::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $page->update(['status' => 'draft']);
        return response()->json(['success' => true]);
    }

    public function duplicate($id)
    {
        $tenantId = auth()->user()->tenant_id;

        // Same limit as create (MVP guardrail).
        $count = LandingPage::where('tenant_id', $tenantId)->count();
        if ($count >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Limite de 05 páginas atingido. Entre em contato com o suporte para contratar novas páginas.',
            ], 422);
        }

        $page = LandingPage::where('tenant_id', $tenantId)->with('sections')->findOrFail($id);

        return DB::transaction(function () use ($page, $tenantId) {
            $newTitle = trim((string) $page->title) . ' (Cópia)';
            $newTitle = Str::limit($newTitle, 255, '');

            $newPage = LandingPage::create([
                'tenant_id' => $tenantId,
                'title' => $newTitle,
                'slug' => $this->generateUniqueSlug($newTitle),
                'status' => 'draft',
                'settings' => $page->settings ?? [],
            ]);

            foreach ($page->sections as $sec) {
                $newPage->sections()->create([
                    'type' => $sec->type,
                    'content' => $sec->content ?? [],
                    'sort_order' => (int) ($sec->sort_order ?? 0),
                ]);
            }

            return response()->json([
                'success' => true,
                'id' => $newPage->id,
                'builder_url' => url('/ngo/landing-pages/builder/' . $newPage->id),
            ]);
        });
    }

    public function destroy($id)
    {
        $tenantId = auth()->user()->tenant_id;
        $page = LandingPage::where('tenant_id', $tenantId)->findOrFail($id);

        return DB::transaction(function () use ($page) {
            try {
                DB::table('landing_page_leads')->where('landing_page_id', $page->id)->delete();
            } catch (\Throwable $e) {
                // In case the leads table doesn't exist yet in some environments.
                Log::warning('LandingPage: failed deleting leads on page destroy', [
                    'page_id' => $page->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $page->sections()->delete();
            $page->delete();

            return response()->json(['success' => true]);
        });
    }

    public function submitLead(Request $request, $slug)
    {
        // Public route: ignore tenant scopes. Leads should only be captured for published pages
        // (draft capture is allowed only for the owner tenant, for testing).
        $page = LandingPage::withoutGlobalScopes()->where('slug', $slug)->firstOrFail();
        if (($page->status ?? 'draft') !== 'published' && !$this->canViewDraft($page)) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'required|email:rfc,dns|max:255',
            'phone' => 'nullable|string|max:30',
        ]);

        // Limit extra fields to avoid abuse.
        $extra = collect($request->except(['_token', 'name', 'email', 'phone']))
            ->take(20)
            ->map(function ($v) {
                $s = is_scalar($v) ? (string) $v : json_encode($v);
                return mb_substr((string) $s, 0, 500);
            })
            ->toArray();
        
        DB::table('landing_page_leads')->insert([
            'landing_page_id' => $page->id,
            'name' => $validated['name'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'extra_data' => json_encode($extra),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return back()->with('success', 'Dados enviados com sucesso! Entraremos em contato.');
    }

    private function getDefaultContent($type)
    {
        $defaults = [
            'header_nav' => [
                'logo_url' => 'https://via.placeholder.com/150x50?text=LOGO',
                'bg_color' => '#ffffff',
                'text_color' => '#1e293b',
                'links' => [
                    ['label' => 'Início', 'url' => '#'],
                    ['label' => 'Serviços', 'url' => '#services'],
                    ['label' => 'Sobre', 'url' => '#about'],
                    ['label' => 'Contato', 'url' => '#contato']
                ]
            ],
            'who_we_are' => [
                'title' => 'Quem Somos',
                'subtitle' => 'Nossa missão e valores fundamentais',
                'text' => 'Somos uma organização dedicada a transformar realidades através de projetos inovadores e gestão eficiente. Com anos de experiência no mercado, focamos em resultados reais.',
                'image_url' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=800&auto=format&fit=crop',
                'bg_color' => '#f8fafc'
            ],
            'services_grid' => [
                'title' => 'Nossos Serviços',
                'items' => [
                    ['title' => 'Consultoria Especializada', 'desc' => 'Análise profunda e soluções sob medida para seu negócio.', 'image' => 'https://images.unsplash.com/photo-1454165833221-d726baf5957b?q=80&w=400&auto=format&fit=crop'],
                    ['title' => 'Gestão de Projetos', 'desc' => 'Execução precisa com foco em prazos e qualidade.', 'image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=400&auto=format&fit=crop'],
                    ['title' => 'Suporte Técnico', 'desc' => 'Atendimento dedicado para garantir sua operação.', 'image' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?q=80&w=400&auto=format&fit=crop']
                ]
            ],
            'footer_links' => [
                'bg_color' => '#0f172a',
                'text_color' => '#ffffff',
                'company_name' => 'Vivensi Solutions',
                'description' => 'Transformando ideias em impacto real.',
                'facebook' => '#',
                'instagram' => '#',
                'linkedin' => '#'
            ],
            'hero' => [
                'title' => 'Transforme Vidas Hoje',
                'subtitle' => 'Apoie nossa causa e ajude centenas de famílias a ter um futuro melhor através da educação e saúde.',
                'button_text' => 'Quero Ajudar Agora',
                'bg_gradient' => 'linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%)',
                'text_color' => '#ffffff'
            ],
            'products' => [
                'title' => 'Nossos Produtos / Serviços',
                'bg_color' => '#ffffff',
                'items' => [
                    ['name' => 'Consultoria Estratégica', 'price' => 'R$ 1.500', 'image' => 'https://images.unsplash.com/photo-1454165833221-d726baf5957b?q=80&w=300&auto=format&fit=crop', 'link' => '#'],
                    ['name' => 'Gestão de Projetos', 'price' => 'Sob consulta', 'image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=300&auto=format&fit=crop', 'link' => '#']
                ]
            ],
            'video' => [
                'title' => 'Conheça nosso trabalho',
                'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'bg_color' => '#f8fafc'
            ],
            'social_links' => [
                'title' => 'Siga-nos nas redes',
                'facebook' => '#',
                'instagram' => '#',
                'linkedin' => '#',
                'youtube' => '#',
                'bg_color' => '#ffffff'
            ],
            'link_bio' => [
                'profile_image' => 'https://images.unsplash.com/photo-1511367461989-f85a21fda167?q=80&w=150&auto=format&fit=crop',
                'name' => 'Vivensi Solutions',
                'bio' => 'Gestão inteligente para o futuro.',
                'links' => [
                    ['label' => 'Acesse nosso site', 'url' => '#'],
                    ['label' => 'Fale no WhatsApp', 'url' => '#'],
                    ['label' => 'Último Relatório de Impacto', 'url' => '#']
                ],
                'bg_gradient' => 'linear-gradient(180deg, #1e293b 0%, #0f172a 100%)'
            ],
            'lead_capture' => [
                'title' => 'Faça Parte da Mudança',
                'subtitle' => 'Cadastre-se para receber atualizações sobre nossas ações e saiba como ser um voluntário.',
                'button_text' => 'Enviar Meu Cadastro',
                'bg_color' => '#ffffff'
            ],
            'stats' => [
                'title' => 'Nosso Impacto em Números',
                'items' => [
                    ['value' => '500+', 'label' => 'Famílias Atendidas'],
                    ['value' => '15k', 'label' => 'Refeições Distribuídas'],
                    ['value' => '50+', 'label' => 'Projetos Ativos']
                ],
                'bg_color' => '#0f172a',
                'text_color' => '#ffffff'
            ],
            'testimonials' => [
                'title' => 'Vozes de Quem Ajudamos',
                'items' => [
                    ['name' => 'Maria Silva', 'text' => 'A ONG mudou minha vida e a dos meus filhos.', 'role' => 'Beneficiária'],
                    ['name' => 'João Souza', 'text' => 'O apoio recebido foi fundamental para meu negócio.', 'role' => 'Empreendedor Local']
                ]
            ],
            'newsletter' => [
                'title' => 'Fique por dentro das novidades',
                'subtitle' => 'Receba nosso informativo semanal diretamente no seu e-mail.',
                'button_text' => 'Assinar Agora',
                'bg_color' => '#eff6ff'
            ],
            'whatsapp' => [
                'phone' => '5511999999999',
                'message' => 'Olá! Gostaria de saber mais sobre como posso contribuir para a ONG.'
            ],
            'features' => [
                'title' => 'Nossos Eixos de Atuação',
                'items' => [
                    ['title' => 'Educação Infantil', 'desc' => 'Programas de contraturno escolar e reforço.'],
                    ['title' => 'Saúde Comunitária', 'desc' => 'Atendimento básico e orientação nutricional.'],
                    ['title' => 'Cultura e Lazer', 'desc' => 'Oficinas de música, teatro e esportes.']
                ]
            ],
            'about' => [
                'title' => 'Nossa Jornada',
                'text' => 'Fundada em 2010, nossa organização nasceu com o propósito de reduzir a desigualdade em nossa região através de ações diretas e sustentáveis.',
                'image_url' => 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=1470&auto=format&fit=crop'
            ],
            'contact' => [
                'title' => 'Onde nos Encontrar',
                'address' => 'Av. da Solidariedade, 1000 - Centro',
                'email' => 'contato@ongvivensi.org',
                'phone' => '(11) 4002-8922'
            ],
            // NEW BLOCKS (MVP designs)
            'cta_banner' => [
                'title' => 'Junte-se à nossa missão',
                'subtitle' => 'Uma ação simples hoje pode transformar realidades amanhã.',
                'button_text' => 'Quero participar',
                'button_url' => '#contato',
                'bg_gradient' => 'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)',
                'text_color' => '#ffffff'
            ],
            'faq' => [
                'title' => 'Perguntas Frequentes',
                'subtitle' => 'Tire suas dúvidas rapidamente.',
                'items' => [
                    ['q' => 'Como posso contribuir?', 'a' => 'Você pode doar, ser voluntário(a) ou compartilhar nossa campanha.'],
                    ['q' => 'Minha doação é segura?', 'a' => 'Sim. Usamos canais confiáveis e registramos tudo com transparência.'],
                    ['q' => 'Como acompanho o impacto?', 'a' => 'Publicamos relatórios e atualizações periódicas das ações.'],
                ],
                'bg_color' => '#ffffff'
            ],
            'image_gallery' => [
                'title' => 'Galeria',
                'subtitle' => 'Momentos que mostram nosso impacto.',
                'images' => [
                    ['url' => 'https://images.unsplash.com/photo-1520975958225-0df5f2d0b0c9?q=80&w=900&auto=format&fit=crop', 'caption' => 'Ação comunitária'],
                    ['url' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?q=80&w=900&auto=format&fit=crop', 'caption' => 'Oficina e aprendizado'],
                    ['url' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?q=80&w=900&auto=format&fit=crop', 'caption' => 'Equipe e voluntários'],
                    ['url' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?q=80&w=900&auto=format&fit=crop', 'caption' => 'Planejamento e gestão'],
                ],
                'bg_color' => '#f8fafc'
            ],
            'partners_logos' => [
                'title' => 'Parceiros e apoiadores',
                'subtitle' => 'Organizações que caminham com a gente.',
                'logos' => [
                    ['name' => 'Parceiro 1', 'logo_url' => 'https://via.placeholder.com/180x60?text=PARCEIRO+1', 'link' => '#'],
                    ['name' => 'Parceiro 2', 'logo_url' => 'https://via.placeholder.com/180x60?text=PARCEIRO+2', 'link' => '#'],
                    ['name' => 'Parceiro 3', 'logo_url' => 'https://via.placeholder.com/180x60?text=PARCEIRO+3', 'link' => '#'],
                    ['name' => 'Parceiro 4', 'logo_url' => 'https://via.placeholder.com/180x60?text=PARCEIRO+4', 'link' => '#'],
                ],
                'bg_color' => '#ffffff'
            ],
            'steps_timeline' => [
                'title' => 'Como funciona',
                'subtitle' => 'Etapas simples e transparentes.',
                'items' => [
                    ['title' => 'Diagnóstico', 'desc' => 'Entendemos o cenário e definimos prioridades.'],
                    ['title' => 'Plano de ação', 'desc' => 'Organizamos as atividades e responsabilidades.'],
                    ['title' => 'Execução', 'desc' => 'Colocamos em prática com acompanhamento semanal.'],
                    ['title' => 'Prestação de contas', 'desc' => 'Resultados e transparência para doadores e parceiros.'],
                ],
                'bg_color' => '#ffffff'
            ],
            'impact_cards' => [
                'title' => 'Impacto que geramos',
                'subtitle' => 'Resultados que você pode acompanhar.',
                'items' => [
                    ['icon' => 'fa-hand-holding-heart', 'title' => 'Apoio direto', 'desc' => 'Atendimento e suporte contínuo a famílias.'],
                    ['icon' => 'fa-graduation-cap', 'title' => 'Educação', 'desc' => 'Oficinas e ações de formação ao longo do ano.'],
                    ['icon' => 'fa-seedling', 'title' => 'Sustentabilidade', 'desc' => 'Projetos com continuidade e impacto local.'],
                ],
                'bg_color' => '#f8fafc'
            ],
            'before_after' => [
                'title' => 'Antes e Depois',
                'subtitle' => 'Veja, na prática, o resultado do apoio.',
                'left_title' => 'Antes',
                'left_text' => 'Contexto do problema e desafios enfrentados.',
                'left_image_url' => 'https://images.unsplash.com/photo-1496307653780-42ee777d4833?q=80&w=1000&auto=format&fit=crop',
                'right_title' => 'Depois',
                'right_text' => 'Mudanças reais com a força da comunidade.',
                'right_image_url' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?q=80&w=1000&auto=format&fit=crop',
                'bg_color' => '#ffffff'
            ],
            'quick_donation' => [
                'title' => 'Doe em 1 minuto',
                'subtitle' => 'Escolha um valor e ajude agora. Transparência total.',
                'button_text' => 'Quero doar',
                'button_url' => '#contato',
                'options' => [
                    ['label' => 'R$ 25', 'amount' => '25', 'highlight' => false],
                    ['label' => 'R$ 50', 'amount' => '50', 'highlight' => true],
                    ['label' => 'R$ 100', 'amount' => '100', 'highlight' => false],
                ],
                'bg_gradient' => 'linear-gradient(135deg, #0ea5e9 0%, #6366f1 60%, #a855f7 100%)',
                'text_color' => '#ffffff'
            ],
            'pix_donation' => [
                'title' => 'Doe via PIX',
                'subtitle' => 'Use o PIX Copia e Cola abaixo. Se preferir, aponte a câmera para o QR Code.',
                'recipient_name' => 'Sua Organização',
                // Pode ser uma chave PIX simples (telefone/email/cnpj) ou o payload "copia e cola".
                'pix_key_or_payload' => '00020126360014BR.GOV.BCB.PIX0114+5500000000005204000053039865802BR5920Sua Organizacao6009SAO PAULO62070503***6304ABCD',
                'qr_image_url' => 'https://via.placeholder.com/220x220?text=QR+PIX',
                'bg_color' => '#ffffff',
                'text_color' => '#0f172a',
                'help_text' => 'Dica: no app do banco, escolha PIX > Copia e Cola.'
            ],
            'cta_cards' => [
                'title' => 'Como você pode ajudar',
                'subtitle' => 'Escolha a melhor forma de participar.',
                'items' => [
                    ['icon' => 'fa-hand-holding-heart', 'title' => 'Doar agora', 'desc' => 'Contribua com qualquer valor e apoie ações imediatas.', 'button_text' => 'Doar', 'button_url' => '#contato'],
                    ['icon' => 'fa-users', 'title' => 'Ser voluntário(a)', 'desc' => 'Doe seu tempo e ajude diretamente nas atividades.', 'button_text' => 'Quero ser voluntário', 'button_url' => '#contato'],
                    ['icon' => 'fa-share-nodes', 'title' => 'Divulgar', 'desc' => 'Compartilhe nossa campanha e ajude a alcançar mais pessoas.', 'button_text' => 'Compartilhar', 'button_url' => '#contato'],
                ],
                'bg_color' => '#ffffff'
            ],
            'map_embed' => [
                'title' => 'Localização',
                'subtitle' => 'Venha nos visitar ou entre em contato.',
                'address' => 'Av. da Solidariedade, 1000 - Centro',
                'embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3658.6993311165237!2d-46.655981!3d-23.507651!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94ce58a2e2e2e2e3%3A0x1111111111111111!2sS%C3%A3o%20Paulo!5e0!3m2!1spt-BR!2sbr!4v0000000000000',
                'bg_color' => '#f8fafc'
            ],
            'final_cta_form' => [
                'title' => 'Pronto para fazer parte da mudança?',
                'subtitle' => 'Deixe seu contato e receba as próximas atualizações. Se preferir, fale com a gente pelo WhatsApp.',
                'badge' => 'Ação final',
                'button_text' => 'Enviar',
                'bg_gradient' => 'linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #4f46e5 100%)',
                'text_color' => '#ffffff',
                'form_note' => 'Ao enviar, você concorda em receber contato sobre a campanha.'
            ],
            'transparency_numbers' => [
                'title' => 'Transparência em Números',
                'subtitle' => 'Dados claros para você confiar e acompanhar.',
                'items' => [
                    ['value' => 'R$ 120.000', 'label' => 'Investidos em projetos'],
                    ['value' => '1.200+', 'label' => 'Pessoas impactadas'],
                    ['value' => '98%', 'label' => 'Aplicação direta'],
                ],
                'note' => 'Atualizamos estes números periodicamente. Solicite relatórios detalhados quando quiser.',
                'bg_color' => '#ffffff'
            ],
            'team_cards' => [
                'title' => 'Nosso Time',
                'subtitle' => 'Pessoas que fazem a diferença todos os dias.',
                'items' => [
                    [
                        'name' => 'Ana Pereira',
                        'role' => 'Coordenação',
                        'bio' => 'Gestão de projetos e articulação com parceiros.',
                        'photo_url' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?q=80&w=400&auto=format&fit=crop',
                        'linkedin' => '#',
                        'instagram' => '#',
                    ],
                    [
                        'name' => 'Carlos Almeida',
                        'role' => 'Captação',
                        'bio' => 'Relacionamento com doadores e campanhas.',
                        'photo_url' => 'https://images.unsplash.com/photo-1544723795-3fb6469f5b39?q=80&w=400&auto=format&fit=crop',
                        'linkedin' => '#',
                        'instagram' => '#',
                    ],
                    [
                        'name' => 'Mariana Souza',
                        'role' => 'Operações',
                        'bio' => 'Ações em campo e apoio às famílias.',
                        'photo_url' => 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?q=80&w=400&auto=format&fit=crop',
                        'linkedin' => '#',
                        'instagram' => '#',
                    ],
                ],
                'bg_color' => '#ffffff'
            ],
            'campaign_progress' => [
                'title' => 'Meta da Campanha',
                'subtitle' => 'Acompanhe o progresso e ajude a chegar lá.',
                'badge' => 'Transparência',
                'unit' => 'R$',
                'current_amount' => '12.500',
                'goal_amount' => '50.000',
                'note' => 'Última atualização: ' . date('d/m/Y'),
                'bg_color' => '#f8fafc'
            ]
        ];

        return $defaults[$type] ?? [];
    }
}
