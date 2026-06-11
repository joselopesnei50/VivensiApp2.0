<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::all();

        $viewsByPage = DB::table('page_views')
            ->select('page_id', DB::raw('COUNT(*) as total'))
            ->groupBy('page_id')
            ->pluck('total', 'page_id');

        $viewsMonth = DB::table('page_views')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        foreach ($pages as $page) {
            $page->view_count = (int) ($viewsByPage[$page->id] ?? 0);
        }

        $totalViews = $viewsByPage->sum();

        return view('admin.pages.index', compact('pages', 'totalViews', 'viewsMonth'));
    }

    public function edit(Page $page)
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $request->validate([
            'title' => 'required|max:255',
            'slug' => 'required|max:255|unique:pages,slug,' . $page->id,
            'content' => 'required'
        ]);

        $page->update([
            'title'   => $request->title,
            'slug'    => $request->slug,
            'content' => sanitize_user_html($request->content),
        ]);

        return redirect()->route('admin.pages.index')->with('success', 'Página atualizada com sucesso!');
    }
}
