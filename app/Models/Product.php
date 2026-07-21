<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'subtitle',
        'description',
        'price',
        'original_price',
        'category',
        'image',
        'product_images',
        'alt',
        'badge',
        'colors',
        'color_images',
        'selected_color',
        'is_new_arrival',
        'is_best_seller',
        'best_seller_rank',
        'is_on_sale',
        'discount_percentage',
        'stock',
        'sold_count',
        'view_count',
        'rating_avg',
        'rating_count',
    ];

    protected $casts = [
        'colors' => 'array',
        'color_images' => 'array',
        'product_images' => 'array',
        'is_new_arrival' => 'boolean',
        'is_best_seller' => 'boolean',
        'is_on_sale' => 'boolean',
        'sold_count' => 'integer',
        'view_count' => 'integer',
        'rating_avg' => 'decimal:2',
        'rating_count' => 'integer',
    ];

    public function getGalleryImagesAttribute()
    {
        $images = is_array($this->product_images) ? $this->product_images : [];

        $images = collect($images)
            ->filter(fn ($image) => filled($image))
            ->values()
            ->take(20)
            ->toArray();

        if (empty($images) && filled($this->image)) {
            $images = [$this->image];
        }

        return $images;
    }
}
