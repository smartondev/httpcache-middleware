<?php

namespace SmartonDev\HttpCacheMiddleware\Tests\Unit\Http\Middleware\Mocks;

use Psr\Http\Message\ServerRequestInterface;
use SmartonDev\HttpCacheMiddleware\Contracts\LastModifiedResolverInterface;

class LastModifiedResolverMock implements LastModifiedResolverInterface
{
    protected $lastModifiedCallback;

    public function __construct(callable $lastModifiedCallback)
    {
        $this->lastModifiedCallback = $lastModifiedCallback;
    }

    public function resolveLastModifiedTime(ServerRequestInterface $request): ?int
    {
        return call_user_func($this->lastModifiedCallback, $request);
    }
}