<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            ->orderBy('id', 'desc')
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
        $request->validate([
            'title' => 'required|string',
            'sku' => 'required|string|unique:products',
            'description' => 'required|string',
            'product_image' => 'required|array|min:1',
            'product_image.*.file' => 'required|string|min:1',
            'product_variant' => 'required|array|min:1',
            'product_variant.*.tags' => 'required|array|min:1',
            'product_variant_prices' => 'required|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::create([
                'title' => $request->title,
                'sku' => $request->sku,
                'description' => $request->description,
            ]);

            /*+++++++++++++++++++++++ Product Image Insert Into DB +++++++++++++++++++++++*/
            $product_images = [];
            foreach ($request->product_image as $image) {
                Storage::move("products/tmp/" . $image['folder'] . "/" . $image['file'], "products/$product->id/" . $image['file']);
                $product_images[] = [
                    'product_id' => $product->id,
                    'file_path' => "storage/products/$product->id/" . $image['file'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
                Storage::deleteDirectory("products/tmp/" . $image['folder']);
            }

            /*+++++++++++++++++++++++ Product Variant Insert Into DB +++++++++++++++++++++++*/
            $product_variants = [];
            foreach ($request->product_variant as $variant) {
                foreach ($variant['tags'] as $tag) {
                    $prodVariant = ProductVariant::create([
                        'variant' => $tag,
                        'variant_id' => $variant['option'],
                        'product_id' => $product->id
                    ]);

                    $product_variants[] = ['product_variant' => $prodVariant->id, 'variant' => $prodVariant->variant];
                }
            }

            /*+++++++++++++++++++++++ Product Variant Price Insert Into DB +++++++++++++++++++++++*/
            $product_variant_prices = [];
            foreach ($request->product_variant_prices as $price) {
                $variants = explode('/', $price['title']);

                $product_variant_prices[] = [
                    'product_variant_one' => collect($product_variants)->where('variant', $variants[0])->first()['product_variant'],
                    'product_variant_two' => isset($variants[1]) && !empty($variants[1]) ? collect($product_variants)->where('variant', $variants[1])->first()['product_variant'] : null,
                    'product_variant_three' => isset($variants[2]) && !empty($variants[2]) ? collect($product_variants)->where('variant', $variants[2])->first()['product_variant'] : null,
                    'price' => $price['price'],
                    'stock' => $price['stock'],
                    'product_id' => $product->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
            }
            ProductVariantPrice::insert($product_variant_prices);

            DB::commit();
            return response()->json(['message' => 'Successfully added product.'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
        }
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
        $productVariant = ProductVariant::where('product_id', $product->id)->get(['variant_id', 'variant']);

        $product_variant = [];
        foreach ($productVariant->groupBy('variant_id') as $key => $value) {
            $product_variant[] = ['option' => $key, 'tags' => $value->pluck('variant')->toArray()];
        }
        $product_variant = collect($product_variant);

        $productVariantPrices = ProductVariantPrice::with('ProductVariantOne', 'ProductVariantTwo', 'ProductVariantThree')
            ->where('product_id', $product->id)->get();

        $product_variant_prices = [];
        foreach ($productVariantPrices as $variantPrice) {
            $product_variant_prices[] = [
                "title" => $variantPrice->ProductVariantOne->variant . "/" . (!is_null($variantPrice->ProductVariantTwo) ? $variantPrice->ProductVariantTwo->variant . "/" : "") . (!is_null($variantPrice->ProductVariantThree) ? $variantPrice->ProductVariantThree->variant . "/" : ""),
                "price" => $variantPrice->price,
                "stock" => $variantPrice->stock
            ];
        }
        $product_variant_prices = collect($product_variant_prices);

        return view('products.edit', compact('variants', 'product', 'product_variant', 'product_variant_prices'));
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
