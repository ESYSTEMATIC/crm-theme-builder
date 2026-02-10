<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SiteVersion extends Model
{
    protected $connection = 'platform';

    protected $table = 'site_versions';

    protected $fillable = [
        'site_id',
        'version',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function payload(): HasOne
    {
        return $this->hasOne(SiteVersionPayload::class);
    }
}
