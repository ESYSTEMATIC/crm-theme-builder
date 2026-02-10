<?php

namespace App\Http\Controllers;

use App\Services\ThemeRegistry;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;

class ThemeAssetController extends Controller
{
    private ThemeRegistry $registry;

    private const MIME_TYPES = [
        'js' => 'application/javascript',
        'css' => 'text/css',
        'html' => 'text/html',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
    ];

    public function __construct(ThemeRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * GET /api/theme-assets/{themeKey}/{path}
     *
     * Serve a theme asset file (JS, CSS, fonts, images).
     */
    public function show(string $themeKey, string $path): Response
    {
        $assetsDir = $this->registry->getAssetsPath($themeKey);
        $filePath = $assetsDir . '/' . $path;

        // Prevent directory traversal
        $realAssets = realpath($assetsDir);
        $realFile = realpath($filePath);

        if (!$realFile || !$realAssets || !str_starts_with($realFile, $realAssets)) {
            return response('Not found', 404);
        }

        if (!File::exists($filePath)) {
            return response('Not found', 404);
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $contentType = self::MIME_TYPES[$ext] ?? 'application/octet-stream';

        return response(File::get($filePath), 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
