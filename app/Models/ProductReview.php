<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'rating',
        'review',
        'is_verified_purchase',
        'is_active',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->hasMany(ProductReviewImage::class);
    }

    public function getIsEditedAttribute(): bool
    {
        if (!$this->created_at || !$this->updated_at) {
            return false;
        }

        return $this->updated_at->gt($this->created_at->copy()->addSeconds(3));
    }
}
