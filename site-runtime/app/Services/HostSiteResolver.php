<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HostSiteResolver
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_PREFIX = 'site_host:';

    /**
     * Resolve a hostname to site data.
     * Returns array with: id, tenant_id, slug, published_version_id, theme_key
     * or null if not found.
     */
    public function resolve(string $host): ?array
    {
        $cacheKey = self::CACHE_PREFIX . $host;

        return Cache::store('redis')->remember($cacheKey, self::CACHE_TTL, function () use ($host) {
            // First, try exact match in site_domains (for custom domains)
            $domain = DB::connection('platform')
                ->table('site_domains')
                ->where('host', $host)
                ->where('status', 'verified')
                ->first();

            if ($domain) {
                return $this->loadSiteData((int) $domain->site_id);
            }

            // Second, try to parse slug from platform wildcard domain
            $slug = $this->parseSlugFromHost($host);
            if ($slug) {
                $site = DB::connection('platform')
                    ->table('sites')
                    ->where('slug', $slug)
                    ->first();

                if ($site) {
                    return $this->buildSiteData($site);
                }
            }

            return null;
        });
    }

    /**
     * Parse slug from hostname like {slug}.crmwebsite.com
     */
    private function parseSlugFromHost(string $host): ?string
    {
        $platformDomain = config('app.platform_domain', 'crmwebsite.com');
        $suffix = '.' . $platformDomain;

        if (str_ends_with($host, $suffix)) {
            $slug = substr($host, 0, -strlen($suffix));
            // Ensure it's a simple slug (no dots = not a subdomain of subdomain)
            if ($slug && !str_contains($slug, '.')) {
                return $slug;
            }
        }

        return null;
    }

    private function loadSiteData(int $siteId): ?array
    {
        $site = DB::connection('platform')
            ->table('sites')
            ->where('id', $siteId)
            ->first();

        if (!$site) {
            return null;
        }

        return $this->buildSiteData($site);
    }

    private function buildSiteData(object $site): array
    {
        $theme = DB::connection('platform')
            ->table('themes')
            ->where('id', $site->theme_id)
            ->first();

        // Get the published version number
        $publishedVersion = null;
        if ($site->published_version_id) {
            $pv = DB::connection('platform')
                ->table('site_versions')
                ->where('id', $site->published_version_id)
                ->first();
            $publishedVersion = $pv ? (int) $pv->version : null;
        }

        // Get the draft version
        $draftVersion = DB::connection('platform')
            ->table('site_versions')
            ->where('site_id', $site->id)
            ->where('status', 'draft')
            ->orderByDesc('version')
            ->first();

        return [
            'id' => (int) $site->id,
            'tenant_id' => $site->tenant_id,
            'slug' => $site->slug,
            'theme_key' => $theme?->key,
            'published_version_id' => $site->published_version_id ? (int) $site->published_version_id : null,
            'published_version_number' => $publishedVersion,
            'draft_version_id' => $draftVersion ? (int) $draftVersion->id : null,
            'draft_version_number' => $draftVersion ? (int) $draftVersion->version : null,
        ];
    }

    /**
     * Validate a preview session token for a given site.
     * Returns the draft version number if valid, null otherwise.
     */
    public function validatePreviewToken(int $siteId, string $token): ?int
    {
        $cacheKey = "preview_token:{$token}";

        return Cache::store('redis')->remember($cacheKey, 60, function () use ($siteId, $token) {
            $session = DB::connection('platform')
                ->table('preview_sessions')
                ->where('token', $token)
                ->where('site_id', $siteId)
                ->where('expires_at', '>', now())
                ->first();

            if (!$session) {
                return null;
            }

            $version = DB::connection('platform')
                ->table('site_versions')
                ->where('id', $session->site_version_id)
                ->first();

            return $version ? (int) $version->version : null;
        });
    }
}
