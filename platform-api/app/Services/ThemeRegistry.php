<?php

namespace App\Services;

use App\Models\Platform\Theme;
use App\Models\Platform\ThemeManifest;
use Illuminate\Support\Facades\File;

class ThemeRegistry
{
    private string $themesPath;

    public function __construct()
    {
        // In Docker: theme-pack is mounted at /packages/theme-pack
        // Locally: it's at ../packages/theme-pack relative to the Laravel app
        $dockerPath = '/packages/theme-pack/themes';
        $localPath = base_path('../packages/theme-pack/themes');
        $this->themesPath = is_dir($dockerPath) ? $dockerPath : $localPath;
    }

    /** Get path to a specific theme directory. */
    public function getThemePath(string $themeKey): string
    {
        return $this->themesPath . '/' . $themeKey;
    }

    /** Get manifest array for a theme. */
    public function getManifest(string $themeKey): array
    {
        $path = $this->getThemePath($themeKey) . '/manifest.json';
        if (!File::exists($path)) {
            throw new \RuntimeException("Manifest not found for theme: {$themeKey}");
        }
        return json_decode(File::get($path), true);
    }

    /** Get default payload for a theme. */
    public function getDefaultPayload(string $themeKey): array
    {
        $path = $this->getThemePath($themeKey) . '/defaults/payload.json';
        if (!File::exists($path)) {
            throw new \RuntimeException("Default payload not found for theme: {$themeKey}");
        }
        return json_decode(File::get($path), true);
    }

    /** Sync all themes from disk to database. */
    public function syncAll(): array
    {
        $synced = [];
        $themeDirs = File::directories($this->themesPath);

        foreach ($themeDirs as $themeDir) {
            $manifestPath = $themeDir . '/manifest.json';
            if (!File::exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode(File::get($manifestPath), true);
            $key = $manifest['key'];
            $checksum = md5_file($manifestPath);

            $theme = Theme::updateOrCreate(
                ['key' => $key],
                ['name' => $manifest['name'], 'version' => $manifest['version'], 'is_active' => true]
            );

            $existingManifest = $theme->manifest;
            if (!$existingManifest || $existingManifest->checksum !== $checksum) {
                ThemeManifest::updateOrCreate(
                    ['theme_id' => $theme->id],
                    ['manifest_json' => $manifest, 'checksum' => $checksum]
                );
            }

            $synced[] = $key;
        }

        return $synced;
    }

    /** List all available theme keys from disk. */
    public function availableThemes(): array
    {
        $themes = [];
        foreach (File::directories($this->themesPath) as $dir) {
            $manifestPath = $dir . '/manifest.json';
            if (File::exists($manifestPath)) {
                $manifest = json_decode(File::get($manifestPath), true);
                $themes[] = $manifest['key'];
            }
        }
        return $themes;
    }
}
