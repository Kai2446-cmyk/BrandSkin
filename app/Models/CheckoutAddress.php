<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckoutAddress extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'address_line',
        'district',
        'city',
        'province',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'map_link',
        'courier_note',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];
}
