<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckoutOrderItem extends Model
{
    protected $fillable = [
        'checkout_order_id',
        'product_id',
        'product_name',
        'product_image',
        'price',
        'quantity',
        'reviewed_at',
    ];

    protected $casts = [
        'price' => 'integer',
        'quantity' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(CheckoutOrder::class, 'checkout_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
