<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $connection = 'tenant';

    protected $table = 'leads';

    protected $fillable = [
        'site_id',
        'name',
        'email',
        'phone',
        'message',
        'property_id',
        'source',
    ];
}
