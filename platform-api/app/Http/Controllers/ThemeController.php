<?php

namespace App\Http\Controllers;

use App\Models\Platform\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ThemeController extends Controller
{
    /**
     * GET /api/themes
     *
     * Return all active themes with their manifests.
     */
    public function index(): JsonResponse
    {
        $themes = Theme::with('manifest')
            ->where('is_active', true)
            ->get();

        return response()->json([
            'data' => $themes,
        ]);
    }
}
