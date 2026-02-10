<?php

namespace App\Http\Controllers\Public;

use App\Models\Platform\Site;
use App\Models\Tenant\Lead;
use App\Services\TenantConnectionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class LeadController extends Controller
{
    public function __construct(
        private TenantConnectionManager $tenantManager
    ) {}

    /**
     * POST /api/public/leads
     *
     * Create a new lead from a microsite contact form submission.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'site_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'message' => 'nullable|string|max:5000',
            'property_id' => 'nullable|integer',
            'source' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed.',
                'messages' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Look up the site to get the tenant ID
        $site = Site::find($validated['site_id']);

        if (!$site) {
            return response()->json([
                'error' => 'Site not found.',
            ], 404);
        }

        // Connect to the tenant's database
        $this->tenantManager->connect($site->tenant_id);

        // Create the lead in the tenant database
        $lead = Lead::create([
            'site_id' => $site->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'message' => $validated['message'] ?? null,
            'property_id' => $validated['property_id'] ?? null,
            'source' => $validated['source'] ?? null,
        ]);

        return response()->json([
            'data' => $lead,
        ], 201);
    }
}
