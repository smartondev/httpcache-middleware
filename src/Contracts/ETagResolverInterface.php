<?php

namespace SmartonDev\HttpCacheMiddleware\Contracts;

use Psr\Http\Message\ServerRequestInterface;
use SmartonDev\HttpCache\Builders\ETagHeaderBuilder;

/**
 * Interface for resolving the ETag for a given request.
 */
interface ETagResolverInterface
{
    /**
     * Resolve the ETag for a given request.
     *
     * @param ServerRequestInterface $request
     * @return string|null|ETagHeaderBuilder The ETag for the request. If null is returned, ETag will not be checked.
     */
    public function resolveETag(ServerRequestInterface $request): null|string|ETagHeaderBuilder;
}