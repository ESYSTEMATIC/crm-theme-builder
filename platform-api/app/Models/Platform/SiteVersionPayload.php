<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteVersionPayload extends Model
{
    protected $connection = 'platform';

    protected $table = 'site_version_payloads';

    protected $fillable = [
        'site_version_id',
        'payload_json',
        'checksum',
    ];

    protected $casts = [
        'payload_json' => 'array',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(SiteVersion::class, 'site_version_id');
    }
}
