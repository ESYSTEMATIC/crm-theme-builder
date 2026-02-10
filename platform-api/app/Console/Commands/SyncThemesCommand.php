<?php

namespace App\Console\Commands;

use App\Services\ThemeRegistry;
use Illuminate\Console\Command;

class SyncThemesCommand extends Command
{
    protected $signature = 'themes:sync';

    protected $description = 'Sync all themes from the theme-pack directory into the database';

    public function handle(ThemeRegistry $registry): int
    {
        $this->info('Syncing themes from disk...');

        try {
            $synced = $registry->syncAll();

            if (empty($synced)) {
                $this->warn('No themes found to sync.');
                return self::SUCCESS;
            }

            foreach ($synced as $key) {
                $this->line("  Synced: {$key}");
            }

            $this->info(count($synced) . ' theme(s) synced successfully.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to sync themes: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
