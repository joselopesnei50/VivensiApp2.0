<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'title',
        'video_url',
        'document_url',
        'duration_minutes',
        'type',
        'order',
    ];

    /**
     * Endereço do player embutido a partir do link colado pelo admin.
     * Aceita YouTube (watch?v=, youtu.be/, shorts/, embed/, com ou sem &t=) e Vimeo.
     * Devolve null se não reconhecer — a tela mostra um link para abrir o vídeo.
     */
    public function embedUrl(): ?string
    {
        return static::toEmbedUrl($this->video_url);
    }

    public static function toEmbedUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $yt = '/(?:youtube(?:-nocookie)?\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/|v\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/';
        if (preg_match($yt, $url, $m)) {
            $start = null;
            if (preg_match('/[?&#](?:t|start)=(\d+)/', $url, $t)) {
                $start = (int) $t[1];
            }
            return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0&modestbranding=1' . ($start ? '&start=' . $start : '');
        }

        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        return null;
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
                    ->withPivot('finished_at')
                    ->withTimestamps();
    }
}
