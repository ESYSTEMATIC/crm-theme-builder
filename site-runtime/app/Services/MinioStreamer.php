<?php

namespace App\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MinioStreamer
{
    /**
     * Stream a file from MinIO.
     *
     * @param string $objectPath Full path in MinIO bucket
     * @param string $mode 'draft' or 'published'
     * @return StreamedResponse|Response
     */
    public function stream(string $objectPath, string $mode): StreamedResponse|Response
    {
        $disk = Storage::disk('minio');

        if (!$disk->exists($objectPath)) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain']);
        }

        $contentType = $this->guessContentType($objectPath);
        $isAsset = str_contains($objectPath, '/assets/');

        // Determine Cache-Control
        if ($mode === 'draft') {
            $cacheControl = 'no-store';
        } elseif ($isAsset) {
            $cacheControl = 'public, max-age=31536000, immutable';
        } else {
            // HTML files
            $cacheControl = 'public, max-age=300';
        }

        return new StreamedResponse(function () use ($disk, $objectPath) {
            $stream = $disk->readStream($objectPath);
            if ($stream) {
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => $cacheControl,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function guessContentType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'html' => 'text/html; charset=utf-8',
            'js' => 'application/javascript',
            'css' => 'text/css',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            default => 'application/octet-stream',
        };
    }
}
