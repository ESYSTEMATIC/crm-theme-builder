<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantConnectionManager
{
    private ?string $currentTenantId = null;

    /**
     * Configure and connect the tenant database connection for the given tenant.
     */
    public function connect(string $tenantId): void
    {
        if ($this->currentTenantId === $tenantId) {
            return;
        }

        // Resolve the database name for this tenant
        $database = $this->resolveDatabaseName($tenantId);

        Config::set('database.connections.tenant.database', $database);

        DB::purge('tenant');
        DB::reconnect('tenant');

        $this->currentTenantId = $tenantId;
    }

    /**
     * Get the currently connected tenant ID.
     */
    public function getCurrentTenantId(): ?string
    {
        return $this->currentTenantId;
    }

    /**
     * Disconnect the tenant database connection.
     */
    public function disconnect(): void
    {
        DB::purge('tenant');
        $this->currentTenantId = null;
    }

    /**
     * Resolve the database name for a given tenant ID.
     *
     * For MVP this returns the default tenant database configured in the environment.
     * In production, this would look up the tenant's database from a registry.
     */
    private function resolveDatabaseName(string $tenantId): string
    {
        return config('database.connections.tenant.database', 'tenant_sample');
    }
}
