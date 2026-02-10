<?php

namespace App\Http\Middleware;

use App\Models\Platform\Site;
use App\Services\TenantConnectionManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromHost
{
    public function __construct(
        private TenantConnectionManager $tenantManager
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $siteId = $request->header('X-Site-Id') ?? $request->query('site_id') ?? $request->input('site_id');

        if (!$siteId) {
            return response()->json([
                'error' => 'Missing site identifier. Provide X-Site-Id header, site_id query parameter, or site_id in the request body.',
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

        // Store the site on the request for downstream controllers
        $request->attributes->set('site', $site);
        $request->attributes->set('tenant_id', $site->tenant_id);

        return $next($request);
    }
}
