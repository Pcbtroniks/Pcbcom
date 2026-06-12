<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Illuminate\Http\Request $request) {
    $props = [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ];
    if ($request->has('categoria')) {
        $props['initialCategoria'] = (int) $request->query('categoria');
    }
    if ($request->has('busqueda')) {
        $props['initialBusqueda'] = (string) $request->query('busqueda');
    }
    return Inertia::render('Index', $props);
})->name('catalog.index');

Route::get('/productos/{id}', function (int $id) {
    return Inertia::render('Products/Show', ['productoId' => $id]);
})->name('products.show');

Route::get('/recientes', function () {
    return Inertia::render('Catalog/RecentlyViewed');
})->middleware('cart.session')->name('recently-viewed.index');

Route::middleware('cart.session')->get('/cart', function () {
    return Inertia::render('Cart/Index');
})->name('cart.index');

Route::middleware('cart.session')->get('/checkout', function () {
    return Inertia::render('Checkout/Index');
})->name('checkout.index');

Route::get('/orders/{number}', function (string $number) {
    return Inertia::render('Orders/Show', ['orderNumber' => $number]);
})->name('orders.show');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
