@extends('layouts.app')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Product</h1>
    </div>
    <div id="app">
        <update-product
            :variants="{{ $variants }}"
            :product="{{ $product }}"
            :product_images="{{ $product_images }}"
            :product_variant_price_options="{{ $product_variant_prices }}"
            :product_variants="{{ $product_variant }}">Loading
        </update-product>
    </div>
@endsection
