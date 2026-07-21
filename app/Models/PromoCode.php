<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'minimum_purchase',
        'usage_limit',
        'used_count',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'discount_value' => 'integer',
        'minimum_purchase' => 'integer',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function isValidFor(int $subtotal): bool
    {
        if (!$this->is_active) return false;
        if ($this->starts_at && now()->lt($this->starts_at)) return false;
        if ($this->expires_at && now()->gt($this->expires_at)) return false;
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return false;
        if ($this->minimum_purchase && $subtotal < $this->minimum_purchase) return false;

        return true;
    }

    public function discountAmount(int $subtotal): int
    {
        if ($this->discount_type === 'free_shipping') {
            return 0;
        }

        if ($this->discount_type === 'percent') {
            return min($subtotal, (int) floor($subtotal * ($this->discount_value / 100)));
        }

        return min($subtotal, $this->discount_value);
    }
}
