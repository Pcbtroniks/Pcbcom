<?php

namespace App\Http\Controllers\Syscom;

use App\Http\Controllers\Controller;
use App\Services\Syscom\CategoryTreeService;
use App\Services\Syscom\ProductsService;
use Illuminate\Http\Request;

class SyscomProductsController extends Controller
{
    public function __construct(
        protected ProductsService $products,
        protected CategoryTreeService $tree,
    ) {}

    public function index(Request $request)
    {
        $hasFilter = $request->has('categoria')
            || $request->has('marca')
            || $request->has('busqueda');

        if (! $hasFilter) {
            $featured = $this->products->getFeaturedCategoryId();
            if ($featured !== null) {
                $params = array_merge($request->all(), ['categoria' => $featured]);
            } else {
                return response()->json([
                    'error' => 'No hay categorías disponibles en Syscom.',
                    'productos' => [],
                ], 503);
            }
        } else {
            $params = $request->all();
        }

        try {
            $products = $this->products->getProducts($params);
            return response()->json($products);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        try {
            $product = $this->products->getProductById($id);
            if ($product === null) {
                return response()->json(['error' => 'Producto no encontrado'], 404);
            }
            return response()->json($product);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function brands(Request $request)
    {
        $categoria = $request->query('categoria');
        $categoriaId = is_numeric($categoria) ? (int) $categoria : null;
        return response()->json([
            'data' => $this->products->getBrands($categoriaId),
        ]);
    }

    public function categoryTree()
    {
        return response()->json([
            'data' => $this->tree->getRoots(),
        ]);
    }

    public function categoryPath(int $id)
    {
        $path = $this->tree->getPath($id);
        if ($path === []) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        }
        return response()->json(['data' => $path]);
    }
}
