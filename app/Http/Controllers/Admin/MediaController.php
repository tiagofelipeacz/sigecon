<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function public(string $path)
    {
        $path = ltrim($path, '/');

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $full = Storage::disk('public')->path($path);
        $mime = null;

        // Tenta descobrir o MIME de forma segura
        try {
            $mime = Storage::disk('public')->mimeType($path);
        } catch (\Throwable $e) {
            // fallback
        }
        if (!$mime && function_exists('mime_content_type')) {
            $mime = @mime_content_type($full) ?: null;
        }
        $mime = $mime ?: 'application/octet-stream';

        return response()->file($full, [
            'Content-Type'  => $mime,
            'Cache-Control' => 'public, max-age=604800, immutable', // 7 dias
        ]);
    }
}
