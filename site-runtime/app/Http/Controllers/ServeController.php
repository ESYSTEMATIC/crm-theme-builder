<?php

namespace App\Http\Controllers;

use App\Services\HostSiteResolver;
use App\Services\MinioStreamer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServeController extends Controller
{
    public function __construct(
        private HostSiteResolver $resolver,
        private MinioStreamer $streamer
    ) {}

    public function __invoke(Request $request, ?string $path = null): StreamedResponse|Response
    {
        $host = $request->getHost();
        $siteData = $this->resolver->resolve($host);

        if (!$siteData) {
            return new Response('Site not found', 404, ['Content-Type' => 'text/plain']);
        }

        // Determine mode and version
        $mode = 'published';
        $version = $siteData['published_version_number'];

        // Check for preview session cookie
        $previewToken = $request->cookie('preview_session');
        if ($previewToken) {
            $draftVersion = $this->resolver->validatePreviewToken($siteData['id'], $previewToken);
            if ($draftVersion !== null) {
                $mode = 'draft';
                $version = $draftVersion;
            }
        }

        // If no published version and not in preview mode, show "not published" page
        if ($version === null) {
            return new Response(
                '<html><body><h1>Site not published yet</h1><p>This site has not been published. Use a preview link to view the draft.</p></body></html>',
                404,
                ['Content-Type' => 'text/html; charset=utf-8']
            );
        }

        // Map request path to MinIO object path
        $objectPath = $this->resolveObjectPath($siteData['id'], $mode, $version, $path);

        $response = $this->streamer->stream($objectPath, $mode);

        // If 404, try detail route fallback: e.g., /listings/123 -> listings/_detail/index.html
        if ($response->getStatusCode() === 404 && $path) {
            $detailPath = $this->resolveDetailFallback($siteData['id'], $mode, $version, $path);
            if ($detailPath) {
                $response = $this->streamer->stream($detailPath, $mode);
            }
        }

        return $response;
    }

    /**
     * Map URL path to MinIO object path.
     */
    private function resolveObjectPath(int $siteId, string $mode, int $version, ?string $path): string
    {
        $basePath = "sites/{$siteId}/{$mode}/{$version}";

        if (!$path || $path === '/') {
            return "{$basePath}/index.html";
        }

        $path = ltrim($path, '/');

        // If path has a file extension, serve as-is
        if (pathinfo($path, PATHINFO_EXTENSION)) {
            return "{$basePath}/{$path}";
        }

        // Otherwise, treat as directory and serve index.html
        return "{$basePath}/{$path}/index.html";
    }

    /**
     * For paths like /listings/123, try the _detail fallback.
     * Checks if removing the last segment and adding _detail/index.html works.
     */
    private function resolveDetailFallback(int $siteId, string $mode, int $version, string $path): ?string
    {
        $path = trim($path, '/');
        $segments = explode('/', $path);

        if (count($segments) < 2) {
            return null;
        }

        // Remove last segment (the ID) and try _detail path
        array_pop($segments);
        $parentPath = implode('/', $segments);

        return "sites/{$siteId}/{$mode}/{$version}/{$parentPath}/_detail/index.html";
    }
}
