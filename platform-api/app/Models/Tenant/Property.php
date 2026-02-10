<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $connection = 'tenant';

    protected $table = 'properties';

    protected $fillable = [
        'title',
        'address',
        'city',
        'state',
        'zip',
        'price',
        'bedrooms',
        'bathrooms',
        'sqft',
        'description',
        'image_url',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'sqft' => 'integer',
    ];
}
