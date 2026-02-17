<?php

namespace App\Http\Controllers;

use App\Models\Platform\Site;
use App\Models\Platform\SiteVersion;
use App\Models\Platform\SiteVersionPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class PublishController extends Controller
{
    /**
     * POST /api/sites/{id}/publish
     *
     * Publish the current draft version:
     * 1. Mark the draft version as published
     * 2. Set the site's published_version_id
     * 3. Bust site cache so gateway/Nuxt serve fresh content
     * 4. Create a new draft version with the same payload
     */
    public function store(int $id): JsonResponse
    {
        $site = Site::with(['draftVersion.payload'])->find($id);

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

        $draftPayload = $draftVersion->payload?->payload_json ?? [];

        $newDraftVersion = DB::connection('platform')->transaction(function () use ($site, $draftVersion, $draftPayload) {
            // Mark the draft version as published
            $draftVersion->update(['status' => 'published']);

            // Set the site's published version
            $site->update(['published_version_id' => $draftVersion->id]);

            // Create a new draft version with version number incremented
            $newDraftVersion = SiteVersion::create([
                'site_id' => $site->id,
                'version' => $draftVersion->version + 1,
                'status' => 'draft',
                'created_by' => null,
            ]);

            // Copy the payload to the new draft version
            SiteVersionPayload::create([
                'site_version_id' => $newDraftVersion->id,
                'payload_json' => $draftPayload,
                'checksum' => md5(json_encode($draftPayload)),
            ]);

            return $newDraftVersion;
        });

        // Bust gateway Redis cache (raw key, not Laravel-prefixed) so published content is served immediately
        Redis::del("site_host:{$site->slug}." . config('app.platform_domain', 'listacrmsites.com'));

        // Reload the site with updated relations
        $site->load(['theme', 'domains', 'publishedVersion', 'draftVersion']);

        return response()->json([
            'data' => [
                'site' => $site,
                'published_version' => $draftVersion->fresh(),
                'new_draft_version' => $newDraftVersion->load('payload'),
            ],
        ]);
    }
}
