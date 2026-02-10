<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreviewSession extends Model
{
    protected $connection = 'platform';

    protected $table = 'preview_sessions';

    protected $fillable = [
        'site_id',
        'site_version_id',
        'token',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(SiteVersion::class, 'site_version_id');
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }
}
