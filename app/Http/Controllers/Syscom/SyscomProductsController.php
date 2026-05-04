<?php

namespace App\Http\Controllers\Syscom;

use App\Services\Syscom\ProductsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SyscomProductsController extends Controller
{
    public function index()
    {
        try {
            $productsService = new ProductsService();
            $products = $productsService->getProducts();

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
