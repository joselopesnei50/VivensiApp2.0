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

    /** Regra de validação: só imagens rasterizadas (a regra `image` do Laravel 9 aceita SVG). */
    public const RULE = 'mimes:jpg,jpeg,png,webp,gif';

    /**
     * Grava a imagem com a extensão do conteúdo real. Devolve o caminho, ou
     * null se o arquivo não for JPG/PNG/WEBP/GIF de verdade.
     */
    public static function store(UploadedFile $file, string $dir, string $disk = 'public', ?string $basename = null): ?string
    {
        $ext = self::extension($file);
        if (! $ext) {
            return null;
        }
        $name = ($basename ?? \Illuminate\Support\Str::random(40)) . '.' . $ext;
        return $file->storeAs($dir, $name, $disk) ?: null;
    }
}
