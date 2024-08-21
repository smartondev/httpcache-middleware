# HTTP cache header middlewares for PSR-7, PSR-11

**This package is under development, and not ready for production use.**

This package provides a set (eg. Cache-Control, ETag) of HTTP cache handling PSR-7, PSR-11 compatible middlewares.

## Installation

```bash
composer require smartondev/httpcache-middleware
```

## Usage

### Cache-Control

For use `Cache-Control`, `ETag`, `Last-Modified` and more http "caching" headers, you need to add `HttpCacheMiddleware`
to your route.

```php
use SmartonDev\HttpCacheMiddleware\Http\Middleware\HttpCacheMiddleware;
use SmartonDev\HttpCacheMiddleware\Providers\Constants\ProviderConstants;

// add HttpCacheContextService to container with ProviderConstants::HTTP_CACHE_CONTEXT_SERVICE_ID as singleton per request

Route::get('/api/user/{id}', function ($id) {
    $user = User::find($id);
    app()->get(ProviderConstants::HTTP_CACHE_CONTEXT_SERVICE_ID)->getCacheHeaderBuilder()->maxAge(days: 30)->public();
    return response()->json($user);
})->middleware(HttpCacheMiddleware::class);
```

### ETag

For use ETag, you need to add `ETagMatchMiddleware` to your route. You need to implement `ETagResolverInterface` and add
it to the container with `ProviderConstants::ETAG_RESOLVER_SERVICE_ID`. For use ETag you need to
add `HttpCacheContextService` and`HttpCacheMiddleware` to the container
with `ProviderConstants::HTTP_CACHE_CONTEXT_SERVICE_ID`
and `ProviderConstants::HTTP_CACHE_MIDDLEWARE_SERVICE_ID`.

```php
use SmartonDev\HttpCacheMiddleware\Http\Middleware\ETagMatchMiddleware;
use SmartonDev\HttpCacheMiddleware\Http\Middleware\HttpCacheMiddleware;
use SmartonDev\HttpCacheMiddleware\Providers\Constants\ProviderConstants;
use SmartonDev\HttpCacheMiddleware\Contracts\ETagResolverInterface;

class ETagResolver implements ETagResolverInterface
{
    public function resolve($request): string
    {
        // use it own logic to generate ETag
        $id = $request->route('id');
        $etagFromCache = Cache::get('etag_' . $id);
        return $etagFromCache;
    }
}
// add ETagResolver to container with ProviderConstants::ETAG_RESOLVER_SERVICE_ID
// add HttpCacheContextService to container with ProviderConstants::HTTP_CACHE_CONTEXT_SERVICE_ID as singleton pre request

Route::get('/api/user/{id}', function ($id) {
    $user = User::find($id);
    app()->get(ProviderConstants::HTTP_CACHE_CONTEXT_SERVICE_ID)->getCacheHeaderBuilder()->maxAge(days: 30)->public();
    // etag added automatically, or you can set it manually
    return response()->json($user);
})->middleware(HttpCacheMiddleware::class)->middleware(ETagMatchMiddleware::class);
```

### Modified since

```php

```

## Author

- [Márton Somogyi](https://github.com/kamarton)
