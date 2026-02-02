<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function welcome()
    {
        $plans = \App\Models\SubscriptionPlan::where('is_active', true)->get();
        $posts = \App\Models\Post::where('is_published', true)->orderBy('published_at', 'desc')->limit(3)->get();
        $videoUrl = \App\Models\SystemSetting::getValue('home_video_url');
        $testimonials = \App\Models\Testimonial::where('is_active', true)->get();
        return view('welcome', compact('plans', 'posts', 'videoUrl', 'testimonials'));
    }

    public function solutionsNgo()
    {
        \App\Models\LandingPageMetric::track('ngo', 'view');
        session(['lp_source' => 'ngo']);
        return view('public.sales.ngo');
    }

    public function solutionsManager()
    {
        \App\Models\LandingPageMetric::track('manager', 'view');
        session(['lp_source' => 'manager']);
        return view('public.sales.manager');
    }

    public function solutionsCommon()
    {
        \App\Models\LandingPageMetric::track('personal', 'view');
        session(['lp_source' => 'personal']);
        return view('public.sales.personal');
    }

    public function blogIndex()
    {
        $posts = \App\Models\Post::where('is_published', true)->orderBy('published_at', 'desc')->paginate(9);
        return view('public.blog.index', compact('posts'));
    }

    public function blogShow($slug)
    {
        $post = \App\Models\Post::where('slug', $slug)->where('is_published', true)->firstOrFail();
        return view('public.blog.show', compact('post'));
    }

    public function showPage($slug)
    {
        $page = \App\Models\Page::where('slug', $slug)->firstOrFail();
        return view('public.page', compact('page'));
    }
}
