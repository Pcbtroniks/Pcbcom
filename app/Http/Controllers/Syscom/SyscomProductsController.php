<?php

namespace App\Http\Controllers\Syscom;

use App\Services\Syscom\ProductsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SyscomProductsController extends Controller
{
    public function index(Request $request)
    {
        // Validar que se reciba al menos un query param: marca o categoria
        if(!$request->has('marca') && !$request->has('categoria')) {
            return response()->json([
                'error' => 'Se requiere al menos un query param: marca o categoria',
                'products' => []
                ], 400);
        }
        $params = $request->all();
        try {
            $productsService = new ProductsService();
            $products = $productsService->getProducts($params);

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
