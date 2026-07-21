<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPurchase extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'status',
        'purchased_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'purchased_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
