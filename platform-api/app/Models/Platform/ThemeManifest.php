<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeManifest extends Model
{
    protected $connection = 'platform';

    protected $table = 'theme_manifests';

    protected $fillable = [
        'theme_id',
        'manifest_json',
        'checksum',
    ];

    protected $casts = [
        'manifest_json' => 'array',
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
