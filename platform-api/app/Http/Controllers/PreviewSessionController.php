<?php

namespace App\Http\Controllers;

use App\Models\Platform\PreviewSession;
use App\Models\Platform\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class PreviewSessionController extends Controller
{
    /**
     * POST /api/sites/{id}/preview-session
     *
     * Create a preview session with a temporary token.
     */
    public function store(int $id): JsonResponse
    {
        $site = Site::with(['draftVersion.payload', 'theme'])->find($id);

        if (!$site) {
            return response()->json([
                'error' => 'Site not found.',
            ], 404);
        }

        $draftVersion = $site->draftVersion;

        if (!$draftVersion) {
            return response()->json([
                'error' => 'No draft version found for this site.',
            ], 404);
        }

        // Create preview session
        $token = Str::random(64);
        $expiresAt = now()->addMinutes(60);

        $previewSession = PreviewSession::create([
            'site_id' => $site->id,
            'site_version_id' => $draftVersion->id,
            'token' => $token,
            'expires_at' => $expiresAt,
            'created_by' => null,
        ]);

        $platformDomain = config('app.platform_domain', 'crmwebsite.com');
        $runtimeScheme = config('app.runtime_scheme', 'https');
        $runtimePort = config('app.runtime_port', '');
        $portSuffix = $runtimePort ? ":{$runtimePort}" : '';
        $previewUrl = "{$runtimeScheme}://{$site->slug}.{$platformDomain}{$portSuffix}/__preview?token={$token}";

        return response()->json([
            'data' => [
                'preview_url' => $previewUrl,
                'token' => $token,
                'expires_at' => $expiresAt->toIso8601String(),
                'version' => $draftVersion->version,
            ],
        ], 201);
    }
}
