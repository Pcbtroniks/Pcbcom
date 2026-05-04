<?php

namespace App\Http\Controllers\Syscom;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return view('syscom.categories.index');
    }
}
