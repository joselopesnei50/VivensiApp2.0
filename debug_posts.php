<?php

use App\Models\Post;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "Total Posts: " . Post::count() . "\n";
echo "Published Posts: " . Post::where('is_published', true)->count() . "\n";

$posts = Post::all();
foreach($posts as $post) {
    echo "ID: {$post->id} | Title: {$post->title} | Published: {$post->is_published} | Date: {$post->published_at}\n";
}
