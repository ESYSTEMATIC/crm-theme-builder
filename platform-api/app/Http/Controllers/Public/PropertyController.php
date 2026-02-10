<?php

namespace App\Http\Controllers\Public;

use App\Models\Platform\Site;
use App\Models\Tenant\Property;
use App\Services\TenantConnectionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PropertyController extends Controller
{
    public function __construct(
        private TenantConnectionManager $tenantManager
    ) {}

    /**
     * GET /api/public/properties
     *
     * List properties for a given site's tenant, paginated.
     */
    public function index(Request $request): JsonResponse
    {
        $siteId = $request->header('X-Site-Id') ?? $request->query('site_id');

        if (!$siteId) {
            return response()->json([
                'error' => 'Missing site_id query parameter or X-Site-Id header.',
            ], 400);
        }

        $site = Site::find($siteId);

        if (!$site) {
            return response()->json([
                'error' => 'Site not found.',
            ], 404);
        }

        // Connect to the tenant's database
        $this->tenantManager->connect($site->tenant_id);

        $properties = Property::where('status', 'active')
            ->orderByDesc('created_at')
            ->simplePaginate(12);

        return response()->json($properties);
    }

    /**
     * GET /api/public/properties/{id}
     *
     * Get a single property by ID.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $siteId = $request->header('X-Site-Id') ?? $request->query('site_id');

        if (!$siteId) {
            return response()->json([
                'error' => 'Missing site_id query parameter or X-Site-Id header.',
            ], 400);
        }

        $site = Site::find($siteId);

        if (!$site) {
            return response()->json([
                'error' => 'Site not found.',
            ], 404);
        }

        // Connect to the tenant's database
        $this->tenantManager->connect($site->tenant_id);

        $property = Property::find($id);

        if (!$property) {
            return response()->json([
                'error' => 'Property not found.',
            ], 404);
        }

        return response()->json([
            'data' => $property,
        ]);
    }
}
