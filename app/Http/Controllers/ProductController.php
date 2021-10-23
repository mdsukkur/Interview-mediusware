<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Variant;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index()
    {
        $products = Product::with('productVariantPrices', 'productVariantPrices.ProductVariantOne', 'productVariantPrices.ProductVariantTwo', 'productVariantPrices.ProductVariantThree')
            ->when(request('title'), function ($q) {
                $q->where('title', 'like', "%" . request('title') . "%");
            })
            ->when(request('date'), function ($q) {
                $q->whereDate('created_at', request('date'));
            })
            ->when(request('variant'), function ($q) {
                $q->whereHas('productVariant', function ($query) {
                    $query->where('variant', request('variant'));
                });
            })
            ->when(request('price_from') || request('price_to'), function ($q) {
                $q->whereHas('productVariantPrices', function ($query) {
                    if (request('price_from') && request('price_to'))
                        $query->whereBetween('price', [request('price_from'), request('price_to')]);
                    elseif (request('price_from'))
                        $query->where('price', '>=', request('price_from'));
                    elseif (request('price_to'))
                        $query->where('price', '>=', request('price_to'));
                });
            })
            ->paginate(5);

        $variants = Variant::select('title', 'id')->with('variant_items:variant_id,variant')->get();
        return view('products.index', compact('products', 'variants'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {

    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        return view('products.edit', compact('variants'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
