<?php

namespace App\Http\Controllers;

use App\Models\Platform\Site;
use App\Models\Platform\SiteDomain;
use App\Models\Platform\SiteVersion;
use App\Models\Platform\SiteVersionPayload;
use App\Models\Platform\Theme;
use App\Services\ThemeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SiteController extends Controller
{
    public function __construct(
        private ThemeRegistry $themeRegistry
    ) {}

    /**
     * POST /api/sites
     *
     * Create a new site with an initial draft version and platform subdomain.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|string|max:255',
            'theme_key' => 'required|string|max:255',
            'slug' => 'required|string|max:255|alpha_dash|unique:platform.sites,slug',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed.',
                'messages' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Find the theme by key
        $theme = Theme::where('key', $validated['theme_key'])->where('is_active', true)->first();

        if (!$theme) {
            return response()->json([
                'error' => "Theme not found or inactive: {$validated['theme_key']}",
            ], 404);
        }

        // Load the default payload for this theme
        try {
            $defaultPayload = $this->themeRegistry->getDefaultPayload($validated['theme_key']);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }

        $site = DB::connection('platform')->transaction(function () use ($validated, $theme, $defaultPayload) {
            // Create the site
            $site = Site::create([
                'tenant_id' => $validated['tenant_id'],
                'theme_id' => $theme->id,
                'slug' => $validated['slug'],
            ]);

            // Create the initial draft version
            $version = SiteVersion::create([
                'site_id' => $site->id,
                'version' => 1,
                'status' => 'draft',
                'created_by' => null,
            ]);

            // Create the payload for the draft version
            SiteVersionPayload::create([
                'site_version_id' => $version->id,
                'payload_json' => $defaultPayload,
                'checksum' => md5(json_encode($defaultPayload)),
            ]);

            // Create the platform subdomain domain entry
            $platformDomain = config('app.platform_domain', 'crmwebsite.com');
            SiteDomain::create([
                'site_id' => $site->id,
                'host' => "{$validated['slug']}.{$platformDomain}",
                'type' => 'platform_subdomain',
                'status' => 'verified',
                'verified_at' => now(),
            ]);

            return $site;
        });

        $site->load(['theme', 'domains', 'draftVersion.payload']);

        return response()->json([
            'data' => $site,
        ], 201);
    }

    /**
     * GET /api/sites/{id}
     *
     * Return a site with its theme, domains, and published version info.
     */
    public function show(int $id): JsonResponse
    {
        $site = Site::with(['theme.manifest', 'domains', 'publishedVersion', 'draftVersion'])
            ->find($id);

        if (!$site) {
            return response()->json([
                'error' => 'Site not found.',
            ], 404);
        }

        return response()->json([
            'data' => $site,
        ]);
    }
}
