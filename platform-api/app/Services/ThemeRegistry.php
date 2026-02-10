<?php

namespace App\Services;

use App\Models\Platform\Theme;

class ThemeRegistry
{
    /**
     * Get default payload for a theme from the database.
     */
    public function getDefaultPayload(string $themeKey): array
    {
        $theme = Theme::where('key', $themeKey)->first();

        if (!$theme || !$theme->default_payload_json) {
            throw new \RuntimeException("Default payload not found for theme: {$themeKey}");
        }

        return $theme->default_payload_json;
    }
}
