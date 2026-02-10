<?php

namespace App\Http\Controllers;

use App\Models\Platform\Site;
use App\Models\Platform\SiteVersion;
use App\Models\Platform\SiteVersionPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class DraftController extends Controller
{
    /**
     * GET /api/sites/{id}/draft
     *
     * Load the current draft version with its payload.
     */
    public function show(int $id): JsonResponse
    {
        $site = Site::find($id);

        if (!$site) {
            return response()->json([
                'error' => 'Site not found.',
            ], 404);
        }

        $draftVersion = $site->draftVersion()->with('payload')->first();

        if (!$draftVersion) {
            return response()->json([
                'error' => 'No draft version found for this site.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'site_id' => $site->id,
                'version' => $draftVersion,
                'payload' => $draftVersion->payload?->payload_json,
            ],
        ]);
    }

    /**
     * PUT /api/sites/{id}/draft
     *
     * Update the payload of the current draft version.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $site = Site::find($id);

        if (!$site) {
            return response()->json([
                'error' => 'Site not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'payload_json' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed.',
                'messages' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $draftVersion = $site->draftVersion;

        if (!$draftVersion) {
            // Create a new draft version if none exists
            $latestVersion = SiteVersion::where('site_id', $site->id)
                ->max('version') ?? 0;

            $draftVersion = SiteVersion::create([
                'site_id' => $site->id,
                'version' => $latestVersion + 1,
                'status' => 'draft',
                'created_by' => null,
            ]);
        }

        $payloadJson = $validated['payload_json'];
        $checksum = md5(json_encode($payloadJson));

        SiteVersionPayload::updateOrCreate(
            ['site_version_id' => $draftVersion->id],
            [
                'payload_json' => $payloadJson,
                'checksum' => $checksum,
            ]
        );

        $draftVersion->load('payload');

        return response()->json([
            'data' => [
                'site_id' => $site->id,
                'version' => $draftVersion,
                'payload' => $draftVersion->payload->payload_json,
            ],
        ]);
    }
}
