<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Site extends Model
{
    protected $connection = 'platform';

    protected $table = 'sites';

    protected $fillable = [
        'tenant_id',
        'theme_id',
        'slug',
        'published_version_id',
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SiteVersion::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(SiteDomain::class);
    }

    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(SiteVersion::class, 'published_version_id');
    }

    public function draftVersion(): HasOne
    {
        return $this->hasOne(SiteVersion::class)
            ->where('status', 'draft')
            ->orderByDesc('version');
    }

    public function previewSessions(): HasMany
    {
        return $this->hasMany(PreviewSession::class);
    }
}
