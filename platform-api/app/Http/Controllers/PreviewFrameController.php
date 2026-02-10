<?php

namespace App\Http\Controllers;

use App\Models\Platform\Site;
use App\Services\ThemeRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class PreviewFrameController extends Controller
{
    private ThemeRegistry $registry;

    public function __construct(ThemeRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * GET /api/sites/{id}/preview-frame
     *
     * Returns rendered HTML for the in-editor live preview iframe.
     * Asset paths are rewritten to use /api/theme-assets/{themeKey}/.
     */
    public function show(Request $request, int $id): Response
    {
        $site = Site::with(['theme', 'draftVersion.payload'])->find($id);

        if (!$site) {
            return response('Site not found', 404);
        }

        $themeKey = $site->theme->key ?? null;
        if (!$themeKey) {
            return response('Theme not found', 404);
        }

        // Get the draft payload
        $draftPayload = [];
        if ($site->draftVersion && $site->draftVersion->payload) {
            $raw = $site->draftVersion->payload->payload_json;
            $draftPayload = is_string($raw) ? json_decode($raw, true) : $raw;
        }

        // Determine which route to render initially
        $routeId = $request->query('routeId', '');
        $manifest = $this->registry->getManifest($themeKey);
        $routes = $manifest['routes'] ?? [];

        // Default to first route if not specified
        if (!$routeId && count($routes) > 0) {
            $routeId = $routes[0]['id'] ?? '';
        }

        // Build the __MICROSITE__ config
        $routePayload = $draftPayload['routes'][$routeId] ?? ['seo' => ['title' => ''], 'sections' => []];
        $seoTitle = ($routePayload['seo']['title'] ?? 'Preview') . ($draftPayload['settings']['seo']['titleSuffix'] ?? '');

        $micrositeJson = [
            'siteId' => $site->id,
            'mode' => 'draft',
            'version' => $site->draftVersion->version ?? 1,
            'themeKey' => $themeKey,
            'settings' => $draftPayload['settings'] ?? [],
            'routeId' => $routeId,
            'route' => $routePayload,
            'apiBaseUrl' => '',
        ];

        // Read base template and rewrite asset paths
        $html = $this->registry->getBaseTemplate($themeKey);

        // Rewrite /assets/... to /api/theme-assets/{themeKey}/...
        $html = preg_replace(
            '#(href|src)="/assets/#',
            '$1="/api/theme-assets/' . $themeKey . '/',
            $html
        );

        // Inject <base href="/"> so relative URLs resolve through the Vite proxy
        $html = str_replace('<head>', '<head><base href="/">', $html);

        // Replace template placeholders
        $html = str_replace('{{SEO_TITLE}}', htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8'), $html);
        $html = str_replace('{{MICROSITE_JSON}}', json_encode($micrositeJson, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $html);

        // Temporary diagnostic: visible debug banner to confirm JS executes in iframe
        $debugScript = <<<'HTML'
<script>
(function(){
  var d = document.createElement('div');
  d.id = '__debug';
  d.style.cssText = 'position:fixed;top:0;left:0;right:0;padding:8px;background:red;color:#fff;font:bold 14px sans-serif;z-index:99999;text-align:center;';
  d.textContent = 'DEBUG: inline JS ran, waiting for DOMContentLoaded...';
  document.documentElement.appendChild(d);
  document.addEventListener('DOMContentLoaded', function(){
    d.style.background = 'green';
    d.textContent = 'DEBUG: DOMContentLoaded fired, sections=' + (window.__MICROSITE__?.route?.sections?.length || 0);
    var root = document.getElementById('microsite-root');
    d.textContent += ', root=' + (root ? 'found' : 'MISSING') + ', children=' + (root ? root.children.length : 0);
    setTimeout(function(){ d.remove(); }, 5000);
  });
})();
</script>
HTML;
        $html = str_replace('</head>', $debugScript . '</head>', $html);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }
}
