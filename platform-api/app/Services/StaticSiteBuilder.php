<?php

namespace App\Services;

use App\Models\Platform\Site;
use App\Models\Platform\SiteVersion;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StaticSiteBuilder
{
    public function __construct(
        private ThemeRegistry $themeRegistry
    ) {}

    /**
     * Build static HTML files for a site version and upload to MinIO.
     */
    public function build(Site $site, SiteVersion $siteVersion, array $payload, string $mode): void
    {
        $themeKey = $site->theme->key;
        $version = $siteVersion->version;
        $basePath = "sites/{$site->id}/{$mode}/{$version}";
        $manifest = $this->themeRegistry->getManifest($themeKey);
        $baseTemplate = $this->themeRegistry->getBaseTemplate($themeKey);
        $settings = $payload['settings'] ?? [];
        $routes = $payload['routes'] ?? [];

        // Upload theme assets
        $this->uploadAssets($themeKey, $basePath);

        // Generate HTML for each route defined in the manifest
        foreach ($manifest['routes'] as $routeDef) {
            $routeId = $routeDef['id'];
            $routePath = $routeDef['path'];
            $routePayload = $routes[$routeId] ?? [];

            $micrositeJson = [
                'siteId' => $site->id,
                'mode' => $mode,
                'version' => $version,
                'themeKey' => $themeKey,
                'settings' => $settings,
                'routeId' => $routeId,
                'route' => $routePayload,
                'apiBaseUrl' => config('app.api_base_url', ''),
            ];

            $seoTitle = ($routePayload['seo']['title'] ?? 'Page')
                . ($settings['seo']['titleSuffix'] ?? '');

            $html = str_replace(
                ['{{SEO_TITLE}}', '{{MICROSITE_JSON}}'],
                [
                    e($seoTitle),
                    json_encode($micrositeJson, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ],
                $baseTemplate
            );

            $objectPath = $this->routeToObjectPath($routePath, $basePath);

            $cacheControl = $mode === 'published'
                ? 'public, max-age=300'
                : 'no-store';

            Storage::disk('minio')->put($objectPath, $html, [
                'ContentType' => 'text/html; charset=utf-8',
                'CacheControl' => $cacheControl,
            ]);

            Log::info("Built page: {$objectPath}");
        }
    }

    /**
     * Upload theme assets (JS, CSS, images, fonts) to MinIO.
     */
    private function uploadAssets(string $themeKey, string $basePath): void
    {
        $assetsDir = $this->themeRegistry->getAssetsPath($themeKey);
        if (!is_dir($assetsDir)) {
            return;
        }

        $files = File::allFiles($assetsDir);
        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $objectKey = "{$basePath}/assets/{$relativePath}";
            $contentType = $this->guessContentType($file->getExtension());
            $cacheControl = 'public, max-age=31536000, immutable';

            Storage::disk('minio')->put($objectKey, $file->getContents(), [
                'ContentType' => $contentType,
                'CacheControl' => $cacheControl,
            ]);
        }
    }

    /**
     * Convert a route path to an object storage path.
     *
     * "/" -> "{basePath}/index.html"
     * "/about" -> "{basePath}/about/index.html"
     * "/listings" -> "{basePath}/listings/index.html"
     * "/listings/:id" -> "{basePath}/listings/_detail/index.html"
     */
    private function routeToObjectPath(string $routePath, string $basePath): string
    {
        // Detail routes like /listings/:id -> listings/_detail/index.html
        if (str_contains($routePath, ':')) {
            $parentPath = preg_replace('#/:[^/]+$#', '', $routePath);
            $parentPath = ltrim($parentPath, '/');
            return "{$basePath}/{$parentPath}/_detail/index.html";
        }

        $routePath = ltrim($routePath, '/');
        if ($routePath === '') {
            return "{$basePath}/index.html";
        }
        return "{$basePath}/{$routePath}/index.html";
    }

    /**
     * Guess the MIME content type from a file extension.
     */
    private function guessContentType(string $extension): string
    {
        return match (strtolower($extension)) {
            'js' => 'application/javascript',
            'css' => 'text/css',
            'html' => 'text/html; charset=utf-8',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'woff2' => 'font/woff2',
            'woff' => 'font/woff',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'ico' => 'image/x-icon',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
