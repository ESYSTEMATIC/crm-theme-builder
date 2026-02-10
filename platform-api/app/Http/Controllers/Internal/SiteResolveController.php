<?php

namespace App\Http\Controllers\Internal;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class SiteResolveController extends Controller
{
    /**
     * GET /api/internal/site-payload/{siteId}?mode=published|draft&version=N
     *
     * Returns everything a Nuxt theme server needs to render a page:
     * manifest, payload (settings + routes), and theme_key.
     */
    public function payload(int $siteId): JsonResponse
    {
        $mode = request()->query('mode', 'published');
        $version = request()->query('version');

        $site = DB::connection('platform')
            ->table('sites')
            ->where('id', $siteId)
            ->first();

        if (!$site) {
            return response()->json(['error' => 'Site not found'], 404);
        }

        // Determine which version to load
        if ($mode === 'draft') {
            $siteVersion = DB::connection('platform')
                ->table('site_versions')
                ->where('site_id', $siteId)
                ->where('status', 'draft')
                ->when($version, fn ($q) => $q->where('version', $version))
                ->orderByDesc('version')
                ->first();
        } else {
            if ($site->published_version_id) {
                $siteVersion = DB::connection('platform')
                    ->table('site_versions')
                    ->where('id', $site->published_version_id)
                    ->first();
            } else {
                return response()->json(['error' => 'Site not published'], 404);
            }
        }

        if (!$siteVersion) {
            return response()->json(['error' => 'Version not found'], 404);
        }

        // Get payload
        $payloadRow = DB::connection('platform')
            ->table('site_version_payloads')
            ->where('site_version_id', $siteVersion->id)
            ->first();

        $payloadJson = $payloadRow ? $payloadRow->payload_json : '{}';
        $payload = is_string($payloadJson) ? json_decode($payloadJson, true) : $payloadJson;

        // Get theme + manifest
        $theme = DB::connection('platform')
            ->table('themes')
            ->where('id', $site->theme_id)
            ->first();

        $manifest = null;
        if ($theme) {
            $manifestRow = DB::connection('platform')
                ->table('theme_manifests')
                ->where('theme_id', $theme->id)
                ->first();
            if ($manifestRow) {
                $mJson = $manifestRow->manifest_json;
                $manifest = is_string($mJson) ? json_decode($mJson, true) : $mJson;
            }
        }

        return response()->json([
            'site_id' => $site->id,
            'tenant_id' => $site->tenant_id,
            'slug' => $site->slug,
            'theme_key' => $theme?->key,
            'mode' => $mode,
            'version' => $siteVersion->version,
            'manifest' => $manifest,
            'payload' => $payload,
        ]);
    }
}
