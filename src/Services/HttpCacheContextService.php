<?php

namespace SmartonDev\HttpCacheMiddleware\Services;

use SmartonDev\HttpCache\CacheHeaderBuilder;
use SmartonDev\HttpCache\ETagHeaderBuilder;
use SmartonDev\HttpCacheMiddleware\Contracts\HttpCacheContextServiceInterface;

/**
 * Service for handling the cache context.
 */
class HttpCacheContextService implements HttpCacheContextServiceInterface
{
    protected CacheHeaderBuilder $cacheHeaderBuilder;

    /**
     * Allowed HTTP methods for caching.
     *
     * If the request method is not in this list, the cache headers only added if no cache is set.
     *
     * @var array|string[]
     */
    protected array $cacheCacheableHttpMethods = ['GET', 'HEAD'];

    /**
     * Allowed HTTP status codes for caching.
     *
     * If the response status code is not in this list, the cache headers only added if no cache is set.
     *
     * @var array|int[]
     */
    protected array $cacheCacheabledHttpStatusCodes = [
        200,
        201,
        203,
        204,
        206,
        404,
    ];

    public function __construct()
    {
        $this->cacheHeaderBuilder = (new CacheHeaderBuilder())
            ->withNoCache();
    }

    public function withEtag(string|ETagHeaderBuilder $etag): static
    {
        $this->cacheHeaderBuilder->etag($etag);
        return $this;
    }

    public function withoutEtag(): static
    {
        $this->cacheHeaderBuilder->resetEtag();
        return $this;
    }

    public function withCacheHeaderBuilder(CacheHeaderBuilder $cacheHeaderBuilder): static
    {
        $this->cacheHeaderBuilder = $cacheHeaderBuilder;
        return $this;
    }

    public function withNoCache(): static
    {
        $this->cacheHeaderBuilder->noCache();
        return $this;
    }

    public function withCacheableHttpMethod(array $methods): static
    {
        $this->cacheCacheableHttpMethods = $methods;
        return $this;
    }

    public function getCacheableHttpMethods(): array
    {
        return $this->cacheCacheableHttpMethods;
    }

    public function withCacheableHttpStatusCodes(array $statusCodes): static
    {
        $this->cacheCacheabledHttpStatusCodes = $statusCodes;
        return $this;
    }

    public function getCacheableHttpStatusCodes(): array
    {
        return $this->cacheCacheabledHttpStatusCodes;
    }

    public function getCacheHeaderBuilder(): CacheHeaderBuilder
    {
        return $this->cacheHeaderBuilder;
    }

    public function hasEtag(): bool
    {
        return $this->cacheHeaderBuilder->hasEtag();
    }

    public function getEtag(): ?string
    {
        return $this->cacheHeaderBuilder->getEtag();
    }

    public function getHeaders(): array
    {
        return $this->cacheHeaderBuilder->toHeaders();
    }

    public function hasHeaders(): bool
    {
        return !$this->cacheHeaderBuilder->isEmpty();
    }

    public function isNoCache(): bool
    {
        return $this->cacheHeaderBuilder->isNoCache();
    }

    public function withLastModified(int $lastModified): static
    {
        $this->cacheHeaderBuilder->lastModified($lastModified);
        return $this;
    }

    public function hasLatModified(): bool
    {
        return $this->cacheHeaderBuilder->hasLastModified();
    }
}