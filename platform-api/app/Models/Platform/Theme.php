<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Theme extends Model
{
    protected $connection = 'platform';

    protected $table = 'themes';

    protected $fillable = [
        'key',
        'name',
        'version',
        'is_active',
        'default_payload_json',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_payload_json' => 'array',
    ];

    public function manifest(): HasOne
    {
        return $this->hasOne(ThemeManifest::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }
}
