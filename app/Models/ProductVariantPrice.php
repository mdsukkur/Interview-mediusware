<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{
    protected $fillable = [
        'product_variant_one',
        'product_variant_two',
        'product_variant_three',
        'price',
        'stock',
        'product_id',
    ];

    public function ProductVariantOne()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_one')->select(['id', 'variant']);
    }

    public function ProductVariantTwo()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_two')->select(['id', 'variant']);
    }

    public function ProductVariantThree()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_three')->select(['id', 'variant']);
    }
}
