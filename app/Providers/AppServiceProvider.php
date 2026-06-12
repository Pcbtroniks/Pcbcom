<?php

namespace App\Providers;

use App\Http\Middleware\EnsureCartSession;
use App\Listeners\MergeGuestCart;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CartService;
use App\Services\Cart\CheckoutService;
use App\Services\Cart\PricingService;
use App\Services\Payment\NullGateway;
use App\Services\Payment\PaymentGateway;
use App\Services\Payment\StripeGateway;
use App\Services\Payment\TransferGateway;
use App\Services\Syscom\CartCheckoutService;
use App\Services\Syscom\CategoryTreeService;
use App\Services\Syscom\SyscomHttpClient;
use App\Services\Syscom\TokenCache;
use App\Services\Syscom\WishlistService;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TokenCache::class, function () {
            return new TokenCache(
                cacheKey: (string) config('syscom.token.cache_key'),
                safetyMarginSeconds: (int) config('syscom.token.safety_margin_seconds', 60),
            );
        });

        $this->app->singleton(SyscomHttpClient::class, function ($app) {
            return new SyscomHttpClient(
                tokenCache: $app->make(TokenCache::class),
                baseUrl: (string) config('syscom.api_url'),
            );
        });

        $this->app->singleton(WishlistService::class, function ($app) {
            return new WishlistService($app->make(SyscomHttpClient::class));
        });

        $this->app->singleton(CategoryTreeService::class, function ($app) {
            return new CategoryTreeService($app->make(\App\Services\Syscom\CategoriesService::class));
        });

        $this->app->singleton(CartCheckoutService::class, function ($app) {
            return new CartCheckoutService($app->make(SyscomHttpClient::class));
        });

        $this->app->singleton(CartResolver::class, fn () => new CartResolver());

        $this->app->singleton(PricingService::class, function ($app) {
            return new PricingService($app->make(\App\Services\Syscom\ProductsService::class));
        });

        $this->app->singleton(CartService::class, function ($app) {
            return new CartService(
                resolver: $app->make(CartResolver::class),
                pricing: $app->make(PricingService::class),
                products: $app->make(\App\Services\Syscom\ProductsService::class),
                wishlist: $app->make(WishlistService::class),
            );
        });

        $this->app->singleton(PaymentGateway::class, function ($app) {
            $name = (string) config('payment.gateway', 'null');
            return match ($name) {
                'stripe' => new StripeGateway(),
                'transfer' => new TransferGateway(),
                default => new NullGateway(),
            };
        });

        $this->app->singleton(CheckoutService::class, function ($app) {
            return new CheckoutService(
                pricing: $app->make(PricingService::class),
                syscom: $app->make(CartCheckoutService::class),
                gateway: $app->make(PaymentGateway::class),
            );
        });
    }

    public function boot(): void
    {
        EncryptCookies::except(['cart_token']);

        $this->configureRateLimiters();
        $this->registerEventListeners();
        $this->registerMiddlewareAliases();
    }

    protected function configureRateLimiters(): void
    {
        $perMinute = (int) config('syscom.rate_limit.per_minute', 60);

        RateLimiter::for('syscom', function (Request $request) use ($perMinute) {
            $key = optional($request->user())->id
                ? 'user:'.$request->user()->id
                : 'ip:'.$request->ip();

            return Limit::perMinute($perMinute)->by($key);
        });
    }

    protected function registerEventListeners(): void
    {
        Event::listen(Login::class, MergeGuestCart::class);
    }

    protected function registerMiddlewareAliases(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('cart.session', EnsureCartSession::class);
    }
}
