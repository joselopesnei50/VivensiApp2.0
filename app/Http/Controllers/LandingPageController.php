<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\LandingPageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LandingPageController extends Controller
{
    public function index()
    {
        $pages = LandingPage::where('tenant_id', auth()->user()->tenant_id)->get();
        return view('ngo.landing_pages.index', compact('pages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $page = LandingPage::create([
            'tenant_id' => auth()->user()->tenant_id,
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . rand(100, 999),
            'status' => 'draft',
            'settings' => [
                'theme_color' => '#4f46e5',
                'font_family' => 'Inter'
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
        $page = LandingPage::where('slug', $slug)->firstOrFail();
        $sections = $page->sections;
        
        return view('landing_pages.render', compact('page', 'sections'));
    }

    public function publish($id)
    {
        $page = LandingPage::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $page->update(['status' => 'published']);
        return response()->json(['success' => true]);
    }

    public function submitLead(Request $request, $slug)
    {
        $page = LandingPage::where('slug', $slug)->firstOrFail();
        
        \DB::table('landing_page_leads')->insert([
            'landing_page_id' => $page->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'extra_data' => json_encode($request->except(['_token', 'name', 'email', 'phone'])),
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
            ]
        ];

        return $defaults[$type] ?? [];
    }
}
