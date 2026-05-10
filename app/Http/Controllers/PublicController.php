<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function welcome()
    {
        $plans = \App\Models\SubscriptionPlan::where('is_active', true)->get();
        $posts = \App\Models\Post::where('is_published', 1)
                    ->where('published_at', '<=', now())
                    ->orderBy('published_at', 'desc')
                    ->limit(3)
                    ->get();
        $videoUrl    = \App\Models\SystemSetting::getValue('home_video_url');
        $testimonials = \App\Models\Testimonial::where('is_active', true)->get();

        // Estatísticas reais do banco como fallback
        $realTenants  = \App\Models\Tenant::count();
        $realProjects = \App\Models\Project::count();
        $realUsers    = \App\Models\User::where('role', '!=', 'super_admin')->count();

        $siteStats = [
            'orgs_count'    => \App\Models\SystemSetting::getValue('stat_orgs_count',    $realTenants),
            'orgs_label'    => \App\Models\SystemSetting::getValue('stat_orgs_label',    'organizações já na plataforma'),
            'projects_count'=> \App\Models\SystemSetting::getValue('stat_projects_count',$realProjects),
            'projects_label'=> \App\Models\SystemSetting::getValue('stat_projects_label','projetos gerenciados'),
            'users_count'   => \App\Models\SystemSetting::getValue('stat_users_count',   $realUsers),
            'users_label'   => \App\Models\SystemSetting::getValue('stat_users_label',   'usuários ativos'),
            'rating_score'  => \App\Models\SystemSetting::getValue('stat_rating_score',  '5.0'),
            'rating_label'  => \App\Models\SystemSetting::getValue('stat_rating_label',  'avaliação média'),
            'hero_badge'    => \App\Models\SystemSetting::getValue('stat_hero_badge',    'Novo — Em fase de lançamento'),
            'impact_label'  => \App\Models\SystemSetting::getValue('stat_impact_label',  'Crescendo a cada dia'),
        ];

        return view('welcome', compact('plans', 'posts', 'videoUrl', 'testimonials', 'siteStats'));
    }

    // ...

    public function blogIndex()
    {
        $posts = \App\Models\Post::where('is_published', 1)
                    ->where('published_at', '<=', now())
                    ->orderBy('published_at', 'desc')
                    ->paginate(9);
        return view('public.blog.index', compact('posts'));
    }

    public function blogShow($slug, Request $request)
    {
        $post = \App\Models\Post::where('slug', $slug)->where('is_published', true)->firstOrFail();
        \App\Models\PostView::record($post, $request);
        return view('public.blog.show', compact('post'));
    }

    public function showPage($slug)
    {
        $page = \App\Models\Page::where('slug', $slug)->firstOrFail();
        return view('public.page', compact('page'));
    }

    public function solutionsNgo()
    {
        $plans = \App\Models\SubscriptionPlan::where('is_active', true)
                    ->where('target_audience', 'ngo')
                    ->get();
        return view('public.solutions_ngo', compact('plans'));
    }

    public function solutionsManager()
    {
        $plans = \App\Models\SubscriptionPlan::where('is_active', true)
                    ->where('target_audience', 'manager')
                    ->get();
        return view('public.solutions_manager', compact('plans'));
    }

    public function solutionsCommon()
    {
        $plans = \App\Models\SubscriptionPlan::where('is_active', true)
                    ->where('target_audience', 'common')
                    ->get();
        return view('public.solutions_common', compact('plans'));
    }
}
