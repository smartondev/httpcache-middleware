<?php

namespace SmartonDev\HttpCacheMiddleware\Contracts;

use SmartonDev\HttpCache\Builders\ETagHeaderBuilder;

/**
 * Interface for the HttpCacheContextService.
 */
interface HttpCacheContextServiceInterface
{
    /**
     * Add ETAG.
     *
     * @param string|ETagHeaderBuilder $etag
     * @return static
     */
    public function withEtag(string|ETagHeaderBuilder $etag): static;

    /**
     * Add last modified.
     *
     * @param int $lastModified
     * @return static
     */
    public function withLastModified(int $lastModified): static;

    /**
     * Check if last modified is set.
     * @return bool
     */
    public function hasLatModified(): bool;

    /**
     * Set no cache.
     * @return static
     */
    public function withNoCache(): static;

    /**
     * Get allowed HTTP methods for caching.
     * @return array
     */
    public function getCacheableHttpMethods(): array;

    /**
     * Get allowed HTTP status codes for caching.
     * @return array
     */
    public function getCacheableHttpStatusCodes(): array;

    /**
     * Check if Etag is set.
     * @return bool
     */
    public function hasEtag(): bool;

    /**
     * Get ETAG.
     * @return int|null
     */
    public function getEtag(): ?string;

    /**
     * Get HTTP headers.
     * @return int|null
     */
    public function getHeaders(): array;

    /**
     * Check if headers are set.
     * @return bool
     */
    public function hasHeaders(): bool;

    /**
     * Check if no cache is set.
     * @return bool
     */
    public function isNoCache(): bool;
}