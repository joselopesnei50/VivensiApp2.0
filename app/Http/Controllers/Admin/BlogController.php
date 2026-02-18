<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index()
    {
        $posts = Post::orderBy('created_at', 'desc')->get();
        return view('admin.blog.index', compact('posts'));
    }

    public function create()
    {
        return view('admin.blog.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'image' => 'nullable|image|max:2048' // Validação de imagem real
        ]);

        $data = $request->except('image');
        $data['slug'] = Str::slug($request->title);
        $data['published_at'] = $request->has('is_published') ? now() : null;

        // Upload de Imagem
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('blog', 'public');
            $data['image'] = '/storage/' . $path; // Salva o caminho acessível
        }

        Post::create($data);

        return redirect()->route('admin.blog.index')->with('success', 'Post criado com sucesso!');
    }

    public function edit(Post $post)
    {
        return view('admin.blog.edit', compact('post'));
    }

    public function update(Request $request, Post $post)
    {
        $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'image' => 'nullable|image|max:2048'
        ]);

        $data = $request->except('image');
        $data['slug'] = Str::slug($request->title);
        $data['is_published'] = $request->has('is_published');
        $data['published_at'] = ($request->has('is_published') && !$post->is_published) ? now() : $post->published_at;

        // Upload de Nova Imagem
        if ($request->hasFile('image')) {
            // (Opcional) Deletar imagem antiga se existir
            // if ($post->image && Storage::exists(str_replace('/storage/', 'public/', $post->image))) {
            //    Storage::delete(str_replace('/storage/', 'public/', $post->image));
            // }

            $path = $request->file('image')->store('blog', 'public');
            $data['image'] = '/storage/' . $path;
        }

        $post->update($data);

        return redirect()->route('admin.blog.index')->with('success', 'Post atualizado com sucesso!');
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return back()->with('success', 'Post excluído com sucesso!');
    }
}
