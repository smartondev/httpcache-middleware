<?php

namespace SmartonDev\HttpCacheMiddleware\Contracts;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface for resolving the last modified time of a request.
 */
interface LastModifiedResolverInterface
{
    /**
     * Resolve the last modified time of a request.
     *
     * @param ServerRequestInterface $request
     * @return int|null
     */
    public function resolveLastModifiedTime(ServerRequestInterface $request): ?int;
}