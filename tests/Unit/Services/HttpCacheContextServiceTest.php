<?php

namespace SmartonDev\HttpCacheMiddleware\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use SmartonDev\HttpCacheMiddleware\Services\HttpCacheContextService;

class HttpCacheContextServiceTest extends TestCase
{
    public function testETag(): void
    {
        $httpCacheContextService = new HttpCacheContextService();
        $this->assertFalse($httpCacheContextService->hasEtag());
        $httpCacheContextService->withEtag('test');
        $this->assertEquals('test', $httpCacheContextService->getEtag());
        $httpCacheContextService->withoutEtag();
        $this->assertFalse($httpCacheContextService->hasEtag());
    }

    public function testEmptyETag(): void
    {
        $httpCacheContextService = new HttpCacheContextService();
        $httpCacheContextService->withEtag('');
        $this->assertFalse($httpCacheContextService->hasEtag());
        $httpCacheContextService->withEtag('   ');
        $this->assertFalse($httpCacheContextService->hasEtag());
    }

    public function testDefaultNoCache(): void
    {
        $httpCacheContextService = new HttpCacheContextService();
        $this->assertTrue($httpCacheContextService->isNoCache());
    }

    public function testDefaultHttpMethods(): void
    {
        $httpCacheContextService = new HttpCacheContextService();
        $this->assertEquals(['GET', 'HEAD'], $httpCacheContextService->getCacheableHttpMethods());
    }

    public function testDefaultHttpStatusCodes(): void
    {
        $httpCacheContextService = new HttpCacheContextService();
        $this->assertEquals([200, 201, 203, 204, 206, 404], $httpCacheContextService->getCacheableHttpStatusCodes());
    }
}