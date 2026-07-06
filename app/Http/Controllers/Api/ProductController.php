<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    /**
     * Display a listing of products with calculated dynamic pricing.
     */
    public function index()
    {
        // Eager load stocks to optimize pricing calculations in Resource
        $products = Product::with('stocks')->get();

        return ProductResource::collection($products);
    }
}
