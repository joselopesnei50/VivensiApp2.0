<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Extensão segura para imagens enviadas a pastas públicas: vem do tipo real
 * do conteúdo (finfo), nunca do nome do arquivo. Só imagens rasterizadas —
 * SVG fica de fora porque pode carregar script.
 */
class SafeImageUpload
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    public static function extension(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();
        if (! $path || ! is_file($path)) {
            return null;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: '';
        return self::ALLOWED[$mime] ?? null;
    }
}
