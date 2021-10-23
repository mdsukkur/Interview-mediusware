<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 'sku', 'description'
    ];

    public function productVariantPrices()
    {
        return $this->hasMany(ProductVariantPrice::class, 'product_id')->select(['product_variant_one', 'product_variant_two', 'product_variant_three', 'price', 'stock', 'product_id']);
    }

    public function productVariant()
    {
        return $this->hasMany(ProductVariant::class, 'product_id')->select(['product_id', 'variant']);
    }
}
