<?php

namespace App\Jobs;

use App\Models\Platform\Site;
use App\Models\Platform\SiteVersion;
use App\Services\StaticSiteBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BuildSiteVersionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $siteId,
        public int $siteVersionId,
        public string $mode
    ) {}

    public function handle(StaticSiteBuilder $builder): void
    {
        $site = Site::with('theme')->findOrFail($this->siteId);
        $siteVersion = SiteVersion::with('payload')->findOrFail($this->siteVersionId);
        $payload = $siteVersion->payload?->payload_json ?? [];

        Log::info("Building site {$site->slug} version {$siteVersion->version} mode {$this->mode}");

        $builder->build($site, $siteVersion, $payload, $this->mode);

        Log::info("Build complete for site {$site->slug} version {$siteVersion->version} mode {$this->mode}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Build failed for site {$this->siteId} version {$this->siteVersionId}: {$exception->getMessage()}");
    }
}
