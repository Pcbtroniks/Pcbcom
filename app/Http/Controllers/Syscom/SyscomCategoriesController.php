<?php

namespace App\Http\Controllers\Syscom;

use \App\Services\Syscom\CategoriesService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SyscomCategoriesController extends Controller
{
    public function index()
    {
        try {
            $categoriesService = new CategoriesService();
            $categories = $categoriesService->getCategories();

            return response()->json($categories);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $categoriesService = new CategoriesService();
            $category = $categoriesService->getCategoryById($id);

            return response()->json($category);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
