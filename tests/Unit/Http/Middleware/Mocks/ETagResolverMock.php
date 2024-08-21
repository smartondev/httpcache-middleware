<?php

namespace SmartonDev\HttpCacheMiddleware\Tests\Unit\Http\Middleware\Mocks;

use Psr\Http\Message\RequestInterface;
use SmartonDev\HttpCacheMiddleware\Contracts\ETagResolverInterface;

class ETagResolverMock implements ETagResolverInterface
{
    private $etagCallback;

    public function __construct(callable $etagCallback)
    {
        $this->etagCallback = $etagCallback;
    }

    public function resolveETag(RequestInterface $request): ?string
    {
        return call_user_func($this->etagCallback, $request);
    }
}