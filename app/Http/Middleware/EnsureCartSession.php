<?php

namespace App\Http\Middleware;

use App\Services\Cart\CartResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCartSession
{
    public function __construct(protected CartResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $cart = $this->resolver->resolve($request);
        $this->resolver->attachCookie($request, $cart);

        $request->attributes->set('cart', $cart);

        return $next($request);
    }
}
