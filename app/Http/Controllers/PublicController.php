<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function welcome()
    {
        $plans = \App\Models\SubscriptionPlan::where('is_active', true)->get();
        // Updated to be safe with int/bool columns and ensure past dates
        $posts = \App\Models\Post::where('is_published', 1)
                    ->where('published_at', '<=', now())
                    ->orderBy('published_at', 'desc')
                    ->limit(3)
                    ->get();
        $videoUrl = \App\Models\SystemSetting::getValue('home_video_url');
        $testimonials = \App\Models\Testimonial::where('is_active', true)->get();
        return view('welcome', compact('plans', 'posts', 'videoUrl', 'testimonials'));
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
